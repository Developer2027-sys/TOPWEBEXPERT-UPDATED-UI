<?php
// Writes redirect-map.csv (web root) from the live rules in app/includes/redirects.php and
// services-config.php, so the file always matches what the router really does.
//
//   php app/scripts/export-redirect-map.php
//
// Columns: pattern, example, status, target, urls_covered, note
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/includes/db.php';
require dirname(__DIR__) . '/includes/settings.php';
require dirname(__DIR__) . '/includes/redirects.php';

$kept = kept_cities();
$keptIn = "'" . implode("','", array_keys($kept)) . "'";
$countRows = fn($sql) => (int)$conn->query($sql)->fetch_row()[0];
$locCount = $countRows("SELECT COUNT(*) FROM locations");
$exampleCity = 'bristol';

// Judgement calls, flagged for review
$notes = [
    'instagram-marketing' => 'No own page; nearest is Social Media Optimization',
    'linkedin-marketing' => 'No own page; nearest is Social Media Optimization',
    'youtube-marketing' => 'No own page; nearest is Social Media Optimization',
    'influencer-marketing' => 'No own page; nearest is Social Media Optimization',
    'lead-generation' => 'REVIEW: no own page; mapped to PPC',
    'real-estate-marketing' => 'REVIEW: no own page; mapped to PPC (the /real-estate-lead-generation-agency landing page may fit better)',
    'email-marketing' => 'REVIEW: no own page; city pages mapped to Content Marketing (guides are 410)',
    'wordpress-development' => 'No own page; nearest is Website Development',
    'landing-page-design' => 'No own page; nearest is Website Development',
    'software-development' => 'REVIEW: no own page; city pages mapped to Website Development (guides are 410)',
    'logo-design' => 'No own page; nearest is Graphic Designing',
    'online-reputation-management' => 'REVIEW: no close service page; city pages go to /our-services (guides are 410)',
];

$rows = [];
$rows[] = ['http://topwebexpert.com/*', 'http://topwebexpert.com/about-us?x=1', 301, 'https://topwebexpert.com/* (path + query kept)', '', '.htaccess'];
$rows[] = ['www.topwebexpert.com/*', 'https://www.topwebexpert.com/local-seo-bristol', 301, 'https://topwebexpert.com/{final path} (one hop)', '', 'Static files: .htaccess; pages: router, combined with any path redirect'];
$rows[] = ['Upper case or trailing slash', '/Local-SEO/', 301, 'lower-case path without slash (or its final target)', '', 'Folded into the same single redirect'];

// City + service pages
$services = services_list();
foreach ($services as $svc => [$name]) {
    $pages = $countRows("SELECT COUNT(*) FROM city_pages cp JOIN locations l ON l.id = cp.location_id WHERE cp.service_slug = '" . $conn->real_escape_string($svc) . "' AND l.slug NOT IN ($keptIn)");
    $rows[] = ["/$svc-{city not kept}", "/$svc-$exampleCity", 301, city_redirect_target($svc), $pages, $notes[$svc] ?? ''];
}
$keptPages = $countRows("SELECT COUNT(*) FROM city_pages cp JOIN locations l ON l.id = cp.location_id WHERE l.slug IN ($keptIn) AND cp.is_active = 1");
$rows[] = ['/{service}-{kept city}', '/local-seo-noida', 200, '(renders; index, follow; self-canonical)', $keptPages, 'Kept cities: config/kept-cities.php'];
foreach (kept_cities_config()['aliases'] as $alias => $real) {
    $rows[] = ["/{service}-$alias", "/local-seo-$alias", 301, "/{service}-$real", '', 'City alias'];
}
$rows[] = ['/{service}-in-{city}', '/local-seo-in-noida', 301, '/{service}-{city} if kept, else the service page', '', 'Old WordPress form'];
$rows[] = ['/{service}/{city}', '/local-seo/noida', 301, '/{service}-{city} if kept, else the service page', '', 'Old two-segment form'];

// Guides
foreach (route_guide_services($conn) as $guide => $svc) {
    $to = guide_redirect_target($svc);
    $rows[] = ["/$guide-{city}", "/$guide-delhi", $to === null ? 410 : 301, $to ?? '(gone)', $locCount,
        $to === null ? 'No close service page (decision 4)' : ($notes[$svc] ?? '')];
}

// Legacy aliases
foreach (legacy_service_aliases() as $alias => $to) {
    $rows[] = ["/$alias and /$alias-{city}", "/$alias-delhi", 301, $to, '', 'Guessed / legacy URL'];
}

// Sitemaps and WordPress leftovers
$rows[] = ['/sitemap-guides-N.xml', '/sitemap-guides-1.xml', 410, '(gone)', 23, 'Old guide sitemaps'];
$rows[] = ['/sitemap-cities-N.xml', '/sitemap-cities-1.xml', 410, '(gone)', '', 'Old city sitemaps'];
$rows[] = ['WordPress sitemaps (sitemap_index.xml, wp-sitemap*.xml, *-sitemap.xml)', '/sitemap_index.xml', 301, '/sitemap.xml', '', ''];
$rows[] = ['/{post-slug}', '/top-11-digital-marketing-trends-in-2023', 301, '/blog/{post-slug}', '', 'WordPress served posts at the root'];
$rows[] = ['/tag/*, /category/*, /author/*, /page/*, /feed, /wp-*, *.html, ?p= ?page_id= ?s=', '/category/seo', 410, '(gone)', '', 'WordPress-only structures'];
$rows[] = ['anything else unknown', '/some-random-nonexistent-page', 404, '(not found)', '', 'Real 404 status'];

$file = WEB_ROOT . '/redirect-map.csv';
$fh = fopen($file, 'w');
fputcsv($fh, ['pattern', 'example', 'status', 'target', 'urls_covered', 'note']);
foreach ($rows as $r) fputcsv($fh, $r);
fclose($fh);
echo count($rows) . " rules written to $file\n";
