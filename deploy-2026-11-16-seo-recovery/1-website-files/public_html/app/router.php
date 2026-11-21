<?php
// Front controller / router. Included by the web-root index.php (WEB_ROOT defined there;
// fall back for safety). App code lives here in /app; assets/libs are at the web root.
if (!defined('WEB_ROOT')) define('WEB_ROOT', dirname(__DIR__));

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$rawPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (!is_string($rawPath) || $rawPath === '') $rawPath = '/';
$path = rtrim($rawPath, '/');
if ($path === '') $path = '/';

// ---- Static files: long cache + optional WebP upgrade (served from web root) ----
$file = WEB_ROOT . $path;
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$staticTypes = [
    'css' => 'text/css', 'js' => 'application/javascript', 'png' => 'image/png',
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', 'gif' => 'image/gif',
    'svg' => 'image/svg+xml', 'ico' => 'image/x-icon', 'woff' => 'font/woff', 'woff2' => 'font/woff2',
    'txt' => 'text/plain', 'xml' => 'application/xml',
];
if ($path !== '/' && isset($staticTypes[$ext]) && file_exists($file) && !is_dir($file)) {
    // Serve pre-generated .webp instead of png/jpg when the browser supports it
    if (in_array($ext, ['png', 'jpg', 'jpeg']) && strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'image/webp') !== false && file_exists($file . '.webp')) {
        $file .= '.webp';
        $ext = 'webp';
    }
    header('Content-Type: ' . $staticTypes[$ext]);
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Vary: Accept');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    return true;
}

// ---- Security gate: manual IP blocks, scraper/probe refusal, security headers ----
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';
security_gate($conn, $path);

require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/redirects.php';

// ---- One host: www.topwebexpert.com served a full duplicate of the site. .htaccess
//      redirects it on Apache; doing it here as well means the PHP dev server behaves the
//      same, and lets the host fix and any path fix go out as ONE redirect below. ----
$canonOrigin = rtrim(site_settings($conn)['site_url'], '/');
$canonHost = strtolower((string)parse_url($canonOrigin, PHP_URL_HOST));
$reqHost = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
// Plain http on the canonical host is handled the same way. Every common "this is
// HTTPS" signal counts, so a TLS-terminating proxy can never cause a redirect loop.
$isHttps = strtolower($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['SERVER_PORT'] ?? '') == 443
    || stripos($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '', 'https') !== false
    || strtolower($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on'
    || stripos($_SERVER['HTTP_CF_VISITOR'] ?? '', 'https') !== false
    || strtolower($_SERVER['REQUEST_SCHEME'] ?? '') === 'https';
$wrongHost = $canonHost !== '' && ($reqHost === 'www.' . $canonHost
    || ($reqHost === $canonHost && !$isHttps && strpos($canonOrigin, 'https://') === 0));
$qs = $_SERVER['QUERY_STRING'] ?? '';
$withQs = fn($p) => $p . ($qs !== '' ? '?' . $qs : '');

// ---- Admin panel ----
if (!$wrongHost && ($path === '/admin' || strpos($path, '/admin/') === 0)) {
    $adminRoutes = [
        '/admin' => 'index.php',
        '/admin/login' => 'login.php',
        '/admin/verify-2fa' => 'verify-2fa.php',
        '/admin/setup-2fa' => 'setup-2fa.php',
        '/admin/logout' => 'logout.php',
        '/admin/blogs' => 'blogs.php',
        '/admin/blog-edit' => 'blog-edit.php',
        '/admin/portfolio' => 'portfolio.php',
        '/admin/portfolio-edit' => 'portfolio-edit.php',
        '/admin/testimonials' => 'testimonials.php',
        '/admin/inquiries' => 'inquiries.php',
        '/admin/traffic' => 'traffic.php',
        '/admin/service-guides' => 'service-guides.php',
        '/admin/service-guide-edit' => 'service-guide-edit.php',
        '/admin/branches' => 'branches.php',
        '/admin/locations' => 'locations.php',
        '/admin/city-pages' => 'city-pages.php',
        '/admin/city-page-edit' => 'city-page-edit.php',
        '/admin/seo' => 'seo.php',
        '/admin/settings' => 'settings.php',
        '/admin/profile' => 'profile.php',
    ];
    if (isset($adminRoutes[$path])) {
        require __DIR__ . '/admin/' . $adminRoutes[$path];
        return true;
    }
    header('Location: /admin');
    return true;
}

// ---- Static page routes (rendered live, like every other page) ----
$routes = [
    '/' => 'home.php',
    '/enquiry-submit' => 'enquiry-submit.php',
    '/about-us' => 'about-us.php',
    '/our-services' => 'our-services.php',
    '/shopify-services' => 'shopify-services.php',
    '/our-portfolio' => 'our-portfolio.php',
    '/blogs' => 'blogs.php',
    '/contact-us' => 'contact-us.php',
    '/thank-you' => 'thank-you.php',
    '/terms-and-conditions' => 'terms-and-conditions.php',
    '/privacy-policy' => 'privacy-policy.php',
    '/web-development-company' => 'web-development-company.php',
    '/website-maintenance-services' => 'website-maintenance-services.php',
    '/ecommerce-website-development-company' => 'ecommerce-website-development-company.php',
    '/search-engine-optimization' => 'search-engine-optimization.php',
    '/social-media-optimization' => 'social-media-optimization.php',
    '/google-marketing' => 'google-marketing.php',
    '/facebook-marketing' => 'facebook-marketing.php',
    '/content-marketing' => 'content-marketing.php',
    '/pay-per-click' => 'pay-per-click.php',
    '/sms-marketing' => 'sms-marketing.php',
    '/whatsapp-marketing' => 'whatsapp-marketing.php',
    '/graphic-designing' => 'graphic-designing.php',
    // Hub pages added October 2026 (content: app/content/*.json, renderer: includes/hub-template.php)
    '/local-seo' => 'local-seo.php',
    '/app-development' => 'app-development.php',
    '/google-my-business' => 'google-my-business.php',
    '/ui-ux-design' => 'ui-ux-design.php',
];

// ---- Redirects, gone URLs and kept city pages (all rules: includes/redirects.php) ----
// URL hygiene is folded in: the rules run on the lower-case, slash-free spelling, and a
// request that differs only in case / trailing slash gets a 301 to that spelling.
$isRead = in_array($method, ['GET', 'HEAD'], true);
$canon = strtolower($path);
$route = resolve_route($conn, $canon, $routes);
$redirectTo = null;
if ($isRead && $route !== null && ($route['status'] ?? 0) === 301) $redirectTo = $route['to'];
elseif ($isRead && $canon !== $rawPath) $redirectTo = $canon;

if ($wrongHost) {
    header('Location: ' . $canonOrigin . $withQs($redirectTo ?? $rawPath), true, 301);
    return true;
}
if ($redirectTo !== null) {
    header('Location: ' . $withQs($redirectTo), true, 301);
    return true;
}
if ($route !== null && ($route['status'] ?? 0) === 410) {
    send_gone();
    return true;
}

// ---- Sitemaps: index + parts (file-cached) ----
if (preg_match('#^/sitemap(?:-([a-z]+))?\.xml$#', $path, $m)) {
    $_GET['part'] = $m[1] ?? 'index';
    require WEB_ROOT . '/sitemap.php';
    return true;
}

// ---- Blog detail: /blog/{slug} — rendered live from the database ----
if (preg_match('#^/blog/([^/]+)$#', $path, $m)) {
    $_GET['slug'] = urldecode($m[1]);
    require __DIR__ . '/blog.php';
    return true;
}

if (isset($routes[$path])) {
    require __DIR__ . '/' . $routes[$path];
    return true;
}

// ---- Kept service + city pages: /{service-slug}-{city-slug} (config/kept-cities.php) ----
if (($route['render'] ?? null) === 'city') {
    $_GET['service_slug'] = $route['service'];
    $_GET['city_slug'] = $route['city'];
    require __DIR__ . '/city-page.php';
    return true;
}

// ---- 404 ----
http_response_code(404);
$pageTitle = 'Page Not Found - TopWebExpert';
$noindex = true;
require __DIR__ . '/includes/header.php';
echo '<section class="py-24 text-center"><h1 class="section-title">404 — Page not found</h1><p class="mt-4 text-ink-soft">The page you are looking for does not exist.</p><a href="/" class="btn-primary mt-8">Back to Home</a></section>';
require __DIR__ . '/includes/footer.php';
return true;
