<?php
// Frontend renderer for service+city pages: /{service-slug}/{city-slug}
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/seo.php';
require_once __DIR__ . '/includes/services-config.php';

$serviceSlug = $_GET['service_slug'] ?? '';
$citySlug = $_GET['city_slug'] ?? '';

$stmt = $conn->prepare("SELECT cp.*, l.city, l.country, l.slug AS city_slug FROM city_pages cp
                        JOIN locations l ON l.id = cp.location_id
                        WHERE cp.service_slug = ? AND l.slug = ? AND cp.is_active = 1 AND l.is_active = 1 LIMIT 1");
$stmt->bind_param('ss', $serviceSlug, $citySlug);
$stmt->execute();
$page = $stmt->get_result()->fetch_assoc();

if (!$page) {
    http_response_code(404);
    $pageTitle = 'Page Not Found - TopWebExpert';
    $noindex = true;
    require __DIR__ . '/includes/header.php';
    echo '<section class="py-24 text-center"><h1 class="section-title">Page not found</h1><a href="/our-services" class="btn-primary mt-8">Our Services</a></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// SEO recovery (October 2026): only the cities in config/kept-cities.php render; the
// router 301s every other /{service}-{city} URL. The old "nearby cities" block linked
// each page to a dozen near-identical ones (doorway pattern), so the sidebar now links
// only to the same service in the other kept cities, plus other services in this city.
$isLive = city_link_filter($conn, array_keys(services_list()), array_keys(kept_cities()));
$coreCities = [];
foreach (kept_cities() as $cs => $cn) {
    if ($cs !== $citySlug && $isLive($serviceSlug, $cs)) $coreCities[] = ['city' => $cn, 'slug' => $cs];
}
$cityServices = [];
if (!is_shopify_service($serviceSlug)) {
    foreach (services_list() as $ss => [$sn]) {
        if ($ss !== $serviceSlug && !is_shopify_service($ss) && $isLive($ss, $citySlug)) $cityServices[$ss] = $sn;
    }
}

// TODO(local content): this is where each kept city page should get content that only
// exists for that city: named local clients, a short case study with numbers, local
// pricing notes and 3-5 city-specific FAQs. Put it in city_pages.content (Admin > City
// Pages > edit) or add a city_pages.local_content column and render it in the
// "local-proof" slot below. Until then these pages rely on the shared service copy.
$localContent = $page['local_content'] ?? '';

// Only the home market x core services may be indexed (config/kept-cities.php
// 'indexable'); every other kept page loads for visitors but is "noindex, follow".
if (!is_indexable_city_page($serviceSlug, $citySlug)) $noindex = 'follow';


// Hand-written meta (Admin > City Pages) is kept when it fits; the generated
// "X in City | Best X Company in City" template never does, so it gets a fresh one.
require_once __DIR__ . '/includes/seo-meta.php';
[$genTitle, $genDesc] = city_page_seo($serviceSlug, $page['service_name'], kept_cities()[$citySlug] ?? $page['city']);
$isTemplated = fn($s) => $s === '' || stripos($s, '| Best ') !== false;
$pageTitle = (!$isTemplated((string)$page['meta_title']) && mb_strlen($page['meta_title']) <= 60) ? $page['meta_title'] : $genTitle;
$metaDescription = (!$isTemplated((string)$page['meta_description']) && mb_strlen($page['meta_description']) <= 155
    && stripos($page['meta_description'], 'Top-rated') !== 0) ? $page['meta_description'] : $genDesc;
$metaKeywords = $page['meta_keywords'] ?? '';
$ogImage = $page['image'] ? '/assets/img/' . $page['image'] : null;
$servicePageUrl = service_hub_url($page['service_slug']);
// Sibling Shopify services for this city (cross-links the 35 Shopify services per city)
$siblingServices = [];
if (is_shopify_service($page['service_slug'])) {
    foreach (services_list() as $ss => [$sn, $si]) {
        if ($ss !== $page['service_slug'] && is_shopify_service($ss) && $isLive($ss, $page['city_slug'])) $siblingServices[$ss] = $sn;
    }
}
$pageUrl = '/' . $page['service_slug'] . '-' . $page['city_slug'];
$jsonLd = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'name' => $page['service_name'],
        'url' => rtrim(site_settings($conn)['site_url'], '/') . $pageUrl,
        'provider' => ['@id' => rtrim(site_settings($conn)['site_url'], '/') . '/#organization'],
        'areaServed' => ['@type' => 'City', 'name' => kept_cities()[$citySlug] ?? $page['city'], 'containedInPlace' => ['@type' => 'Country', 'name' => $page['country']]],
        'description' => $metaDescription,
    ],
    breadcrumb_schema($conn, [
        'Home' => '/',
        'Our Services' => '/our-services',
        $page['service_name'] => $servicePageUrl,
        $page['city'] => $pageUrl,
    ]),
];

require __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<section class="hero-grad text-white py-16 relative overflow-hidden">
  <div class="absolute inset-0 hero-dots opacity-30"></div>
  <div class="absolute -bottom-24 -right-24 w-80 h-80 rounded-full bg-white/10"></div>
  <div class="max-w-5xl mx-auto px-4 text-center relative">
    <nav class="text-white/70 text-xs uppercase tracking-widest" aria-label="Breadcrumb">
      <a href="/" class="hover:text-white">Home</a> / <a href="<?= e($servicePageUrl) ?>" class="hover:text-white"><?= e($page['service_name']) ?></a> / <span class="text-white"><?= e($page['city']) ?></span>
    </nav>
    <h1 class="text-3xl md:text-5xl font-bold mt-5 drop-shadow"><?= e($page['title']) ?></h1>
    <div class="w-24 h-1.5 bg-white/80 rounded-full mx-auto mt-6"></div>
    <p class="mt-5 text-white/90"><?= e($page['service_name']) ?> for businesses in <?= e(location_label($page['city'], $page['country'])) ?></p>
    <a href="/contact-us" class="btn-light mt-8">Get Free Consultation</a>
  </div>
</section>

<!-- Trust band -->
<section class="bg-white border-b border-line">
  <div class="max-w-6xl mx-auto px-4 py-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
    <?php foreach ([['7+', 'Years Experience'], ['150+', 'Happy Clients'], ['75K', 'Leads Generated'], ['500+', 'Projects Delivered']] as [$n, $l]): ?>
    <div class="flex items-center justify-center gap-3">
      <span class="text-3xl font-bold text-accent-dark"><?= e($n) ?></span>
      <span class="text-xs uppercase tracking-wide text-ink-soft text-left leading-tight"><?= e($l) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Content + image -->
<section class="bg-white py-16">
  <div class="max-w-6xl mx-auto px-4 grid lg:grid-cols-3 gap-12">
    <article class="lg:col-span-2 prose-mono">
      <?= $page['content'] /* trusted HTML managed via admin panel */ ?>
      <?php if ($localContent): /* local-proof slot: see the TODO(local content) note above */ ?>
      <div class="local-proof"><?= $localContent /* trusted HTML managed via admin panel */ ?></div>
      <?php endif; ?>
      <a href="/contact-us" class="btn-primary mt-4">Contact Us Today</a>
    </article>
    <aside class="space-y-8 self-start">
      <?php if ($page['image']): ?>
      <div class="relative">
        <div class="absolute -inset-3 rounded-3xl bg-accent/15 rotate-2"></div>
        <?php $pageImg = '/assets/img/' . $page['image']; ?>
        <img src="<?= e($pageImg) ?>" alt="<?= e($page['title']) ?>" class="relative w-full h-auto img-card"<?= img_size_attrs($pageImg) ?> loading="lazy" decoding="async">
      </div>
      <?php endif; ?>
      <div class="bg-accent-soft rounded-2xl p-6">
        <h2 class="font-bold text-lg"><?= e($page['service_name']) ?></h2>
        <p class="mt-2 text-sm text-ink-soft">How we work, what is included and answers to common questions.</p>
        <a href="<?= e($servicePageUrl) ?>" class="inline-block mt-4 text-sm font-semibold text-ink hover:text-accent-dark">About this service &rarr;</a>
      </div>
      <?php if ($coreCities): ?>
      <div class="bg-mist rounded-2xl p-6">
        <h2 class="font-bold text-lg"><?= e($page['service_name']) ?> in other cities</h2>
        <ul class="mt-4 grid grid-cols-2 gap-2 text-sm">
          <?php foreach ($coreCities as $c): ?>
            <li><a href="/<?= e($page['service_slug']) ?>-<?= e($c['slug']) ?>" class="text-accent-dark hover:underline"><?= e($c['city']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
      <?php if ($cityServices): ?>
      <div class="bg-mist rounded-2xl p-6">
        <h2 class="font-bold text-lg">More services in <?= e($page['city']) ?></h2>
        <ul class="mt-4 space-y-1.5 text-sm">
          <?php foreach ($cityServices as $ss => $sn): ?>
            <li><a href="/<?= e($ss) ?>-<?= e($page['city_slug']) ?>" class="text-accent-dark hover:underline"><?= e($sn) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
      <?php if ($siblingServices): ?>
      <div class="bg-mist rounded-2xl p-6">
        <h2 class="font-bold text-lg">More Shopify services in <?= e($page['city']) ?></h2>
        <ul class="mt-4 space-y-1.5 text-sm">
          <?php foreach ($siblingServices as $ss => $sn): ?>
            <li><a href="/<?= e($ss) ?>-<?= e($page['city_slug']) ?>" class="text-accent-dark hover:underline"><?= e($sn) ?> in <?= e($page['city']) ?></a></li>
          <?php endforeach; ?>
        </ul>
        <a href="/shopify-services" class="inline-block mt-4 text-sm font-semibold text-ink hover:text-accent-dark">All Shopify services &rarr;</a>
      </div>
      <?php endif; ?>
    </aside>
  </div>
</section>

<!-- CTA -->
<section class="hero-grad text-white py-16 relative overflow-hidden">
  <div class="absolute inset-0 hero-dots opacity-30"></div>
  <div class="max-w-4xl mx-auto px-4 text-center relative">
    <h2 class="text-3xl md:text-4xl font-bold drop-shadow">Grow Your Business in <?= e($page['city']) ?></h2>
    <p class="mt-3 text-white/90">Let's talk about your goals — free consultation, no obligation.</p>
    <a href="/contact-us" class="btn-light mt-8">Contact Us</a>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
