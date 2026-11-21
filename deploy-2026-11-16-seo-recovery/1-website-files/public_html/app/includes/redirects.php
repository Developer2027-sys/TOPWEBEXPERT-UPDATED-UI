<?php
// Every redirect / gone decision for a public URL, in one place (SEO recovery, October 2026).
//
// resolve_route() looks at a lower-cased, trailing-slash-free path and returns
//   ['status' => 301, 'to' => '/final-path']    permanent redirect, always to the FINAL url
//   ['status' => 410]                           gone for good
//   ['render' => 'city', 'service' => s, 'city' => c]   a kept /{service}-{city} page
//   null                                        nothing special: normal routing / 404
//
// Because the result is always the final destination, the router can fold the host fix
// (www -> non-www) and the case/slash fix into the same single 301: no redirect chains.
// The full rule list is exported for review in redirect-map.csv at the web root.
require_once __DIR__ . '/services-config.php';

// All location slugs (active or not: every one of them had generated pages)
function route_location_slugs($conn) {
    static $slugs = null;
    if ($slugs !== null) return $slugs;
    $res = $conn->query("SELECT slug FROM locations");
    if (!$res) db_unavailable($conn->error);
    $slugs = array_fill_keys(array_column($res->fetch_all(), 0), true);
    return $slugs;
}

// Guide template slug => service slug, longest slug first
function route_guide_services($conn) {
    static $guides = null;
    if ($guides !== null) return $guides;
    $guides = [];
    $res = $conn->query("SELECT slug, service_slug FROM service_blogs");
    if ($res === false) {
        // 1146 = table missing (guides feature never migrated): simply no guides. Anything
        // else is a database failure: 503 so search engines retry instead of dropping URLs.
        if ((int)$conn->errno !== 1146) db_unavailable($conn->error);
        return $guides;
    }
    $guides = array_column($res->fetch_all(MYSQLI_ASSOC), 'service_slug', 'slug');
    uksort($guides, fn($a, $b) => strlen($b) - strlen($a));
    return $guides;
}

// A city part of a URL -> [real slug, was it spelled differently?], or null when unknown
function route_city($conn, $part) {
    $alias = kept_city_alias($part);
    if ($alias !== null) return [$alias, true];
    return isset(route_location_slugs($conn)[$part]) ? [$part, false] : null;
}

function resolve_route($conn, $path, array $staticRoutes) {
    // Sitemap addresses from the old WordPress site (Yoast / core) -> the real one
    if (preg_match('#^/(sitemap_index\.xml|wp-sitemap(-[a-z0-9-]+)?\.xml|[a-z0-9-]+-sitemap\d*\.xml|sitemap\.xml\.gz|sitemap\.txt)$#', $path)) {
        return ['status' => 301, 'to' => '/sitemap.xml'];
    }
    // Retired sitemap parts: guide and per-tier city sitemaps listed ~319,000 URLs
    if (preg_match('#^/sitemap-(guides|cities)(-\d+)?\.xml$#', $path)) return ['status' => 410];

    // Bulk-generated blog posts retired in config/kept-blogs.php: 410 Gone. A post that
    // is merely scheduled (future date) but kept falls through to blog.php's 404.
    if (preg_match('#^/blog/([^/]+)$#', $path, $m)) {
        $stmt = $conn->prepare("SELECT id FROM posts WHERE slug = ? LIMIT 1");
        if ($stmt) {
            $slug = urldecode($m[1]);
            $stmt->bind_param('s', $slug);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_row();
            if ($row && is_retired_post_id($row[0])) return ['status' => 410];
        }
        return null;
    }

    if (isset($staticRoutes[$path]) || strpos($path, '/blog/') === 0) return null;

    // Old two-segment form /{service}/{city}: same rules as /{service}-{city}, one hop
    if (preg_match('#^/([a-z0-9-]+)/([a-z0-9-]+)$#', $path, $m) && isset(services_list()[$m[1]])) {
        $r = resolve_route($conn, '/' . $m[1] . '-' . $m[2], $staticRoutes);
        if ($r === null) return null;
        return isset($r['render']) ? ['status' => 301, 'to' => '/' . $r['service'] . '-' . $r['city']] : $r;
    }

    if (preg_match('#^/([a-z0-9-]+)$#', $path, $m)) {
        $slug = $m[1];

        // Guide articles /{guide}-{city}: all retired. 301 to the closest service page,
        // or 410 where no service page is a close match (decided in guide_redirect_target).
        foreach (route_guide_services($conn) as $guide => $service) {
            if (strpos($slug, $guide . '-') !== 0) continue;
            if (!route_city($conn, substr($slug, strlen($guide) + 1))) continue;
            $to = guide_redirect_target($service);
            return $to === null ? ['status' => 410] : ['status' => 301, 'to' => $to];
        }

        // Service + city pages /{service}-{city}
        $services = array_keys(services_list());
        usort($services, fn($a, $b) => strlen($b) - strlen($a));
        foreach ($services as $svc) {
            if (strpos($slug, $svc . '-') !== 0) continue;
            $part = substr($slug, strlen($svc) + 1);
            $legacyIn = strpos($part, 'in-') === 0 && route_city($conn, substr($part, 3));
            $city = route_city($conn, $legacyIn ? substr($part, 3) : $part);
            if (!$city) continue; // not a city: maybe a shorter service slug or an alias matches
            [$citySlug, $respelled] = $city;
            if (!is_kept_city($citySlug)) return ['status' => 301, 'to' => city_redirect_target($svc)];
            if ($legacyIn || $respelled) return ['status' => 301, 'to' => "/$svc-$citySlug"];
            return ['render' => 'city', 'service' => $svc, 'city' => $citySlug];
        }

        // Guessed / legacy service URLs: /seo-services, /seo-services-delhi ...
        foreach (legacy_service_aliases() as $alias => $to) {
            if ($slug === $alias) return ['status' => 301, 'to' => $to];
            if (strpos($slug, $alias . '-') === 0 && route_city($conn, substr($slug, strlen($alias) + 1))) {
                return ['status' => 301, 'to' => $to];
            }
        }

        // WordPress served posts at the root: /{post-slug} -> /blog/{post-slug}
        // (a retired post is 410 here directly: no 301 hop to a 410)
        $stmt = $conn->prepare("SELECT id FROM posts WHERE slug = ? AND date <= CURDATE() LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $slug);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_row();
            if ($row) return is_retired_post_id($row[0]) ? ['status' => 410] : ['status' => 301, 'to' => '/blog/' . $slug];
        }
    }

    // WordPress-only structures that have no equivalent here
    if (preg_match('#^/(tag|category|author|page|comments|feed|wp-content|wp-includes|wp-json)(/|$)|/feed$|\.html$#', $path)
        || (($_SERVER['QUERY_STRING'] ?? '') !== '' && preg_match('/(^|&)(p|page_id|attachment_id|cat|tag|s)=/', $_SERVER['QUERY_STRING']))) {
        return ['status' => 410];
    }
    return null;
}

function send_gone() {
    http_response_code(410);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: public, max-age=86400');
    header('X-Robots-Tag: noindex');
    echo "Gone. This page has been permanently removed.\n";
}
