<?php
// SEO recovery (October 2026) — database part. Code changes alone are not enough on the
// live site, because some of the old wording lives in the database.
//
//   php app/scripts/migrate-seo-recovery.php                  apply
//   php app/scripts/migrate-seo-recovery.php --check          only show what would change
//   php app/scripts/migrate-seo-recovery.php --restore FILE   put back a backup taken by this script
//
// What it does (every row it touches is saved first to app/scripts/data/backup-seo-recovery-*.json):
//  1. Admin > SEO Manager: clears the old meta title / description overrides ("... in Noida",
//     "#1 SEO Company") for the routes in app/includes/seo-meta.php, so the new titles show.
//     Keywords and noindex flags are kept.
//  2. Admin > Settings: address fields from config/business.php, Delhi NCR footer text, and
//     the broken "©" in the footer copyright.
//  3. Admin > Branches: same three offices as config/business.php (others switched off, not
//     deleted), so the admin panel matches what the site shows.
//  4. Clears the cached sitemap files.
// Safe to re-run.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/includes/db.php';
require dirname(__DIR__) . '/includes/settings.php';
require dirname(__DIR__) . '/includes/seo-meta.php';
require dirname(__DIR__) . '/includes/cache.php';

$check = in_array('--check', $argv, true);
$say = fn($m) => print(($check ? '[check] ' : '') . $m . "\n");

// ---- Restore ----
$ri = array_search('--restore', $argv, true);
if ($ri !== false) {
    $b = json_decode((string)@file_get_contents($argv[$ri + 1] ?? ''), true);
    if (!$b) { echo "Usage: --restore app/scripts/data/backup-seo-recovery-....json\n"; exit(1); }
    $conn->query("UPDATE branches SET is_active = 0, is_primary = 0");
    foreach ($b['branches'] as $r) {
        $stmt = $conn->prepare("REPLACE INTO branches (id, name, address, phone, email, is_primary, is_active, sort_order) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param('issssiii', $r['id'], $r['name'], $r['address'], $r['phone'], $r['email'], $r['is_primary'], $r['is_active'], $r['sort_order']);
        $stmt->execute();
    }
    $stmt = $conn->prepare("INSERT INTO settings (skey, svalue) VALUES (?,?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)");
    foreach ($b['settings'] as $k => $v) { $stmt->bind_param('ss', $k, $v); $stmt->execute(); }
    $stmt = $conn->prepare("UPDATE page_seo SET meta_title = ?, meta_description = ? WHERE route = ?");
    foreach ($b['page_seo'] as $r) { $stmt->bind_param('sss', $r['meta_title'], $r['meta_description'], $r['route']); $stmt->execute(); }
    cache_clear_status();
    echo "Restored branches, settings and SEO overrides from {$argv[$ri + 1]}.\n";
    exit;
}

$biz = business_info();
if (!$biz) { echo "config/business.php is missing.\n"; exit(1); }
$locs = $biz['locations'];
$hq = $locs[0];

// ---- Backup ----
$settings = [];
foreach ($conn->query("SELECT skey, svalue FROM settings")->fetch_all() as [$k, $v]) $settings[$k] = $v;
$routes = array_keys(seo_meta_table());
$in = "'" . implode("','", array_map([$conn, 'real_escape_string'], $routes)) . "'";
$seoRows = $conn->query("SELECT route, meta_title, meta_description FROM page_seo WHERE route IN ($in)")->fetch_all(MYSQLI_ASSOC);
$backup = [
    'taken' => date('c'),
    'branches' => $conn->query("SELECT * FROM branches")->fetch_all(MYSQLI_ASSOC),
    'settings' => $settings,
    'page_seo' => $seoRows,
];
if (!$check) {
    $file = __DIR__ . '/data/backup-seo-recovery-' . date('Ymd-His') . '.json';
    file_put_contents($file, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $say('Backup written: ' . $file);
}

// ---- 1. SEO Manager overrides ----
$clear = array_filter($seoRows, fn($r) => $r['meta_title'] !== '' || $r['meta_description'] !== '');
foreach ($clear as $r) $say("SEO override {$r['route']}: clearing \"{$r['meta_title']}\"");
if (!$check && $clear) {
    $conn->query("UPDATE page_seo SET meta_title = '', meta_description = '' WHERE route IN ($in)");
}

// ---- 2. Settings ----
$set = [
    'address' => format_address($hq),
    'address_locality' => $hq['locality'],
    'address_region' => $hq['region'],
    'postal_code' => $hq['postal_code'],
    'phone' => $hq['phone'],
    'email' => $hq['email'],
    'footer_about' => 'TopWebExpert is a website development and digital marketing agency for Delhi NCR, headquartered in Gurugram with an office in Noida. Whether you are just starting out or ready to take your digital presence to the next level, we have the expertise and experience to help you succeed.',
    'footer_copyright' => '© ' . date('Y') . ' TopWebExpert. All rights reserved.',
];
$stmt = $conn->prepare("INSERT INTO settings (skey, svalue) VALUES (?,?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)");
foreach ($set as $k => $v) {
    if (($settings[$k] ?? null) === $v) continue;
    $say("Setting $k = $v");
    if (!$check) { $stmt->bind_param('ss', $k, $v); $stmt->execute(); }
}

// ---- 3. Branches (mirror of config/business.php) ----
$say('Branches: switching off all, then activating the ' . count($locs) . ' offices from config/business.php');
if (!$check) {
    $conn->query("UPDATE branches SET is_active = 0, is_primary = 0");
    foreach ($locs as $i => $loc) {
        $addr = format_address($loc);
        $find = $conn->prepare("SELECT id FROM branches WHERE name = ? OR address = ? LIMIT 1");
        $find->bind_param('ss', $loc['name'], $addr);
        $find->execute();
        $row = $find->get_result()->fetch_row();
        $primary = !empty($loc['primary']) ? 1 : 0;
        if ($row) {
            $u = $conn->prepare("UPDATE branches SET name = ?, address = ?, phone = ?, email = ?, is_primary = ?, is_active = 1, sort_order = ? WHERE id = ?");
            $u->bind_param('ssssiii', $loc['name'], $addr, $loc['phone'], $loc['email'], $primary, $i, $row[0]);
            $u->execute();
        } else {
            $u = $conn->prepare("INSERT INTO branches (name, address, phone, email, is_primary, is_active, sort_order) VALUES (?,?,?,?,?,1,?)");
            $u->bind_param('ssssii', $loc['name'], $addr, $loc['phone'], $loc['email'], $primary, $i);
            $u->execute();
        }
    }
}

// ---- 4. Internal links inside blog posts and kept city pages that point at a redirect ----
// Each href to this site is resolved with the same rules the router uses and, if it is a
// 301, rewritten to the final URL. Changed rows go into a second backup file first.
require_once dirname(__DIR__) . '/includes/redirects.php';
$static = array_fill_keys(array_merge(array_keys(seo_meta_table()), array_keys(service_pages_index())), true);
$fixLinks = function ($html) use ($conn, $static, &$changes) {
    return preg_replace_callback('#href="(?:https?://(?:www\.)?topwebexpert\.com)?(/[^"\#?]*)([^"]*)"#i', function ($m) use ($conn, $static, &$changes) {
        $path = rtrim(strtolower($m[1]), '/') ?: '/';
        $r = resolve_route($conn, $path, $static);
        $to = ($r['status'] ?? 0) === 301 ? $r['to'] : ($path !== $m[1] && isset($static[$path]) ? $path : null);
        if ($to === null) return $m[0];
        $changes[] = "$m[1] -> $to";
        return 'href="' . $to . $m[2] . '"';
    }, $html);
};
$kept = "'" . implode("','", array_map([$conn, 'real_escape_string'], array_keys(kept_cities()))) . "'";
$sources = [
    'posts' => "SELECT id, content FROM posts WHERE content LIKE '%href=%'",
    'city_pages' => "SELECT cp.id, cp.content FROM city_pages cp JOIN locations l ON l.id = cp.location_id WHERE l.slug IN ($kept) AND cp.content LIKE '%href=%'",
];
$linkBackup = [];
foreach ($sources as $table => $sql) {
    foreach ($conn->query($sql)->fetch_all(MYSQLI_ASSOC) as $row) {
        $changes = [];
        $new = $fixLinks($row['content']);
        if (!$changes) continue;
        $say("$table #{$row['id']}: " . implode(', ', array_unique($changes)));
        $linkBackup[$table][$row['id']] = $row['content'];
        if (!$check) {
            $u = $conn->prepare("UPDATE $table SET content = ? WHERE id = ?");
            $u->bind_param('si', $new, $row['id']);
            $u->execute();
        }
    }
}
if (!$check && $linkBackup) {
    $file = __DIR__ . '/data/backup-seo-recovery-links-' . date('Ymd-His') . '.json';
    file_put_contents($file, json_encode($linkBackup, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $say("Original content of changed rows: $file");
}

// ---- 5. Sitemap cache ----
if (!$check) $say('Sitemap cache cleared: ' . cache_clear_status()['deleted'] . ' files');

$say($check ? 'Nothing changed (--check).' : 'Done. Now update the Google Business Profile and social profiles to the same NAP.');
