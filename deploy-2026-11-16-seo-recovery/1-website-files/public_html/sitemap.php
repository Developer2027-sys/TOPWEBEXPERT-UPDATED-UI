<?php
// Sitemap index + parts, cached to disk in app/cache/sitemaps (SITEMAP_CACHE_TTL) —
// the only generated files the site keeps on disk; pages are never cached as files.
// /sitemap.xml         -> index of parts
// /sitemap-pages.xml   -> home, main service pages, about / contact / portfolio / blog list
// /sitemap-blogs.xml   -> published blog posts
// /sitemap-local.xml   -> indexable kept /{service}-{city} pages (config/kept-cities.php)
// Blog posts retired in config/kept-blogs.php are never listed.
// SEO recovery (October 2026): ONLY URLs that answer 200, are indexable and are their own
// canonical. The old /sitemap-guides-N.xml and /sitemap-cities-N.xml parts (~319,000
// URLs, almost all now redirected) answer 410 — see app/includes/redirects.php.
require_once __DIR__ . '/app/includes/db.php';
require_once __DIR__ . '/app/includes/settings.php';
require_once __DIR__ . '/app/includes/cache.php';
require_once __DIR__ . '/app/includes/services-config.php';

$part = $_GET['part'] ?? 'index';
$base = rtrim(site_settings($conn)['site_url'], '/');

$cacheDir = cache_root() . '/sitemaps';
if (!is_dir($cacheDir)) @mkdir($cacheDir, 0775, true);
$cacheFile = $cacheDir . '/' . preg_replace('/[^a-z0-9-]/', '', $part) . '.xml';

if (!in_array($part, ['index', 'pages', 'blogs', 'local'], true)) {
    http_response_code(404);
    exit;
}
header('Content-Type: application/xml; charset=utf-8');
if (is_file($cacheFile) && time() - filemtime($cacheFile) < SITEMAP_CACHE_TTL) {
    readfile($cacheFile);
    exit;
}

function urlset($urls) {
    $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $u) {
        $out .= "  <url>\n    <loc>" . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
        if (!empty($u['lastmod'])) $out .= "    <lastmod>{$u['lastmod']}</lastmod>\n";
        $out .= "  </url>\n";
    }
    return $out . '</urlset>';
}

// Indexable static routes. Anything set to noindex in Admin > SEO Manager is left out.
function sitemap_static_routes($conn) {
    $routes = array_merge(
        ['/', '/about-us', '/our-services', '/our-portfolio', '/blogs', '/contact-us', '/shopify-services'],
        array_keys(service_pages_index()),
        ['/terms-and-conditions', '/privacy-policy']
    );
    $routes = array_values(array_unique($routes));
    $hidden = [];
    $res = @$conn->query("SELECT route FROM page_seo WHERE noindex = 1");
    if ($res) $hidden = array_column($res->fetch_all(), 0);
    return array_values(array_diff($routes, $hidden));
}

// Kept city pages that render (kept city + active row), as [service, city, lastmod]
function sitemap_local_pages($conn) {
    $kept = array_keys(kept_cities());
    $in = "'" . implode("','", array_map([$conn, 'real_escape_string'], $kept)) . "'";
    $res = $conn->query("SELECT cp.service_slug, l.slug, DATE(cp.updated_at) FROM city_pages cp JOIN locations l ON l.id = cp.location_id
                         WHERE cp.is_active = 1 AND l.is_active = 1 AND l.slug IN ($in) ORDER BY l.id, cp.id");
    if (!$res) db_unavailable($conn->error);
    $services = services_list();
    return array_values(array_filter($res->fetch_all(), fn($r) => isset($services[$r[0]]) && is_indexable_city_page($r[0], $r[1])));
}

if ($part === 'index') {
    $today = date('Y-m-d');
    $lm = function ($sql) use ($conn, $today) {
        $r = @$conn->query($sql);
        $v = $r ? $r->fetch_row()[0] : null;
        return $v ? substr($v, 0, 10) : $today;
    };
    $parts = [
        '/sitemap-pages.xml' => $today,
        '/sitemap-blogs.xml' => $lm("SELECT MAX(date) FROM posts WHERE " . posts_live_sql()),
        '/sitemap-local.xml' => max(array_column(sitemap_local_pages($conn), 2) ?: [$today]),
    ];
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($parts as $p => $lastmod) {
        $xml .= "  <sitemap>\n    <loc>" . htmlspecialchars($base . $p, ENT_XML1) . "</loc>\n    <lastmod>$lastmod</lastmod>\n  </sitemap>\n";
    }
    $xml .= '</sitemapindex>';
} elseif ($part === 'pages') {
    $xml = urlset(array_map(fn($r) => ['loc' => $base . $r], sitemap_static_routes($conn)));
} elseif ($part === 'blogs') {
    $urls = [];
    $res = $conn->query("SELECT slug, date FROM posts WHERE " . posts_live_sql() . " ORDER BY date DESC");
    while ($r = $res->fetch_assoc()) {
        $urls[] = ['loc' => $base . '/blog/' . rawurlencode($r['slug']), 'lastmod' => $r['date']];
    }
    $xml = urlset($urls);
} else {
    $xml = urlset(array_map(fn($r) => ['loc' => "$base/{$r[0]}-{$r[1]}", 'lastmod' => $r[2]], sitemap_local_pages($conn)));
}

@file_put_contents($cacheFile, $xml, LOCK_EX);
echo $xml;
