<?php
// Renders full SEO head block: canonical, meta, Open Graph, Twitter, JSON-LD.
// Uses: $pageTitle, $metaDescription, $metaKeywords, $ogImage, $noindex, $jsonLd (array of extra schema blocks)
// Per-route overrides come from the page_seo table (managed in Admin > SEO Manager);
// default titles for main pages from seo-meta.php.
require_once __DIR__ . '/seo-meta.php';
require_once __DIR__ . '/settings.php';

function seo_route_overrides($conn, $route) {
    $stmt = $conn->prepare("SELECT * FROM page_seo WHERE route = ? LIMIT 1");
    $stmt->bind_param('s', $route);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function render_seo($conn, $opts = []) {
    $s = site_settings($conn);
    $route = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    $ovr = seo_route_overrides($conn, $route) ?: [];

    // Main-page titles (seo-meta.php) apply to the page itself only: not to its paginated
    // copies or to a 404 / noindex response served at the same route.
    $table = empty($opts['canonical_query']) && empty($opts['noindex']) ? (seo_meta_table()[$route] ?? [null, null]) : [null, null];
    $title = ($ovr['meta_title'] ?? '') ?: ($table[0] ?? ($opts['title'] ?? $s['site_name']));
    $desc = ($ovr['meta_description'] ?? '') ?: ($table[1] ?? ($opts['description'] ?? ''));
    $keys = ($ovr['meta_keywords'] ?? '') ?: ($opts['keywords'] ?? '');
    $noindex = !empty($ovr['noindex']) || !empty($opts['noindex']);
    // noindex => 'follow' hides the page but lets link value pass through (Tier 3 city / guide pages)
    $robotsFollow = empty($ovr['noindex']) && ($opts['noindex'] ?? null) === 'follow';
    $canonical = rtrim($s['site_url'], '/') . $route . (!empty($opts['canonical_query']) ? '?' . $opts['canonical_query'] : '');
    $ogImage = $opts['og_image'] ?? ($ovr['og_image'] ?? '/assets/logo.png');
    if ($ogImage && $ogImage[0] === '/') $ogImage = rtrim($s['site_url'], '/') . $ogImage;

    echo '<title>' . e($title) . "</title>\n";
    if ($desc) echo '  <meta name="description" content="' . e($desc) . "\">\n";
    if ($keys) echo '  <meta name="keywords" content="' . e($keys) . "\">\n";
    echo '  <meta name="robots" content="' . ($noindex ? ($robotsFollow ? 'noindex, follow' : 'noindex, nofollow') : 'index, follow') . "\">\n";
    echo '  <link rel="canonical" href="' . e($canonical) . "\">\n";
    echo '  <meta name="author" content="' . e($s['site_name']) . "\">\n";

    // Open Graph
    echo '  <meta property="og:type" content="' . e($opts['og_type'] ?? 'website') . "\">\n";
    echo '  <meta property="og:site_name" content="' . e($s['site_name']) . "\">\n";
    echo '  <meta property="og:title" content="' . e($title) . "\">\n";
    if ($desc) echo '  <meta property="og:description" content="' . e($desc) . "\">\n";
    echo '  <meta property="og:url" content="' . e($canonical) . "\">\n";
    echo '  <meta property="og:image" content="' . e($ogImage) . "\">\n";

    // Twitter
    echo '  <meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '  <meta name="twitter:title" content="' . e($title) . "\">\n";
    if ($desc) echo '  <meta name="twitter:description" content="' . e($desc) . "\">\n";
    echo '  <meta name="twitter:image" content="' . e($ogImage) . "\">\n";

    // JSON-LD on every page, one @graph: the Organization, the Gurugram head office as a
    // LocalBusiness (ProfessionalService) with the Noida and Australia offices as its
    // departments, and the WebSite. NAP comes from config/business.php via site_branches().
    $base = rtrim($s['site_url'], '/');
    $sameAs = array_values(array_filter([
        $s['facebook'] ?? '', $s['instagram'] ?? '', $s['linkedin'] ?? '',
        $s['twitter'] ?? '', $s['youtube'] ?? '', $s['medium'] ?? '', $s['google_business_url'] ?? '',
    ]));
    $branches = site_branches($conn);
    $place = function ($br, $id) use ($s, $base) {
        $addr = isset($br['street'])
            ? ['@type' => 'PostalAddress', 'streetAddress' => $br['street'], 'addressLocality' => $br['locality'],
               'addressRegion' => $br['region'], 'postalCode' => $br['postal_code'], 'addressCountry' => $br['country_code']]
            : ['@type' => 'PostalAddress', 'streetAddress' => $br['address'], 'addressCountry' => 'IN'];
        return array_filter([
            '@type' => 'ProfessionalService',
            '@id' => $base . $id,
            // "TopWebExpert - Noida" / "TopWebExpert - Australia"
            'name' => $s['site_name'] . (empty($br['is_primary'])
                ? ' - ' . (($br['country_code'] ?? 'IN') === 'IN' ? ($br['locality'] ?? $br['name']) : $br['country']) : ''),
            'url' => $base . '/',
            'image' => $base . '/assets/logo.png',
            'telephone' => $br['phone'],
            'email' => $br['email'],
            'address' => $addr,
            'openingHours' => $br['hours'] ?? null,
        ]);
    };
    $hq = $branches[0];
    $hqNode = $place($hq, '/#headoffice') + [
        'parentOrganization' => ['@id' => $base . '/#organization'],
        'hasMap' => $s['google_business_url'] ?? '',
        'sameAs' => $sameAs,
    ];
    $depts = [];
    foreach (array_slice($branches, 1) as $i => $br) $depts[] = $place($br, '/#office-' . ($i + 2));
    if ($depts) $hqNode['department'] = $depts;
    $graph = [
        [
            '@type' => 'Organization',
            '@id' => $base . '/#organization',
            'name' => $s['site_name'],
            'url' => $base . '/',
            'logo' => ['@type' => 'ImageObject', 'url' => $base . '/assets/logo.png'],
            'email' => $hq['email'],
            'telephone' => $hq['phone'],
            'address' => $hqNode['address'],
            'sameAs' => $sameAs,
            'subOrganization' => ['@id' => $base . '/#headoffice'],
        ],
        array_filter($hqNode),
        ['@type' => 'WebSite', '@id' => $base . '/#website', 'name' => $s['site_name'], 'url' => $base . '/',
         'publisher' => ['@id' => $base . '/#organization']],
    ];
    $schemas = [['@context' => 'https://schema.org', '@graph' => $graph]];
    foreach (($opts['json_ld'] ?? []) as $extra) $schemas[] = $extra;
    foreach ($schemas as $schema) {
        echo '  <script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
    }
}

// Helper: BreadcrumbList schema
function breadcrumb_schema($conn, $items) {
    $s = site_settings($conn);
    $base = rtrim($s['site_url'], '/');
    $list = [];
    $pos = 1;
    foreach ($items as $name => $url) {
        $list[] = ['@type' => 'ListItem', 'position' => $pos++, 'name' => $name, 'item' => $base . $url];
    }
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $list];
}
