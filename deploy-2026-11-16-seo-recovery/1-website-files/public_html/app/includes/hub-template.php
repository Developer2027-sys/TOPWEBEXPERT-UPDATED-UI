<?php
// Long-form main service page ("hub") renderer, added October 2026 for /local-seo,
// /app-development, /google-my-business and /ui-ux-design. Expects $contentFile set by
// the page stub (e.g. 'local-seo.json'). Section types: text, services, process,
// case-studies, faq. Case studies marked "placeholder": true are NOT shown on the site;
// they stay in the JSON as a reminder until real client results are filled in.
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/seo.php';
require_once __DIR__ . '/services-config.php';

$data = json_decode(file_get_contents(__DIR__ . '/../content/' . $contentFile), true);
$pageTitle = $data['pageTitle'];
$metaDescription = $data['metaDescription'];
$metaKeywords = $data['metaKeywords'] ?? '';
$svcRoute = $data['route'];
$svcName = $data['serviceName'];
$heroImg = '/assets/img/' . $data['image'];
$ogImage = $heroImg;

$base = rtrim(site_settings($conn)['site_url'], '/');
$jsonLd = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'name' => $svcName,
        'serviceType' => $svcName,
        'description' => $metaDescription,
        'url' => $base . $svcRoute,
        'provider' => ['@id' => $base . '/#organization'],
        'areaServed' => [['@type' => 'Country', 'name' => 'India'], ['@type' => 'Country', 'name' => 'Australia']],
    ],
    breadcrumb_schema($conn, ['Home' => '/', 'Our Services' => '/our-services', $svcName => $svcRoute]),
];
foreach ($data['sections'] as $s) {
    if ($s['type'] !== 'faq') continue;
    $jsonLd[] = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(fn($qa) => [
            '@type' => 'Question', 'name' => $qa['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa['a']],
        ], $s['items']),
    ];
}
require __DIR__ . '/header.php';
?>

<!-- Hero -->
<section class="hero-grad text-white py-16 relative overflow-hidden">
  <div class="absolute inset-0 hero-dots opacity-30"></div>
  <div class="absolute -bottom-24 -right-24 w-80 h-80 rounded-full bg-white/10"></div>
  <div class="max-w-5xl mx-auto px-4 text-center relative">
    <nav class="text-white/70 text-xs uppercase tracking-widest" aria-label="Breadcrumb">
      <a href="/" class="hover:text-white">Home</a> / <a href="/our-services" class="hover:text-white">Our Services</a> / <span class="text-white"><?= e($svcName) ?></span>
    </nav>
    <h1 class="text-3xl md:text-5xl font-bold mt-5 drop-shadow"><?= e($data['h1']) ?></h1>
    <div class="w-24 h-1.5 bg-white/80 rounded-full mx-auto mt-6"></div>
    <p class="mt-5 text-white/90 max-w-3xl mx-auto"><?= e($data['tagline']) ?></p>
    <a href="/contact-us" class="btn-light mt-8"><?= e($data['heroButton'] ?? 'Get a Free Consultation') ?></a>
  </div>
</section>

<!-- Trust band (same figures as the rest of the site) -->
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

<!-- Intro -->
<section class="bg-white py-16">
  <div class="max-w-6xl mx-auto px-4 grid md:grid-cols-2 gap-12 items-center">
    <div class="prose-mono">
      <?php foreach ($data['intro'] as $p): ?><p><?= e($p) ?></p><?php endforeach; ?>
    </div>
    <div class="relative">
      <div class="absolute -inset-3 rounded-3xl bg-accent/15 -rotate-2"></div>
      <img src="<?= e($heroImg) ?>" alt="<?= e($data['imageAlt']) ?>" class="relative w-full h-auto img-card"<?= img_size_attrs($heroImg) ?> decoding="async">
    </div>
  </div>
</section>

<?php foreach ($data['sections'] as $si => $s): $bg = $si % 2 === 0 ? 'bg-mist' : 'bg-white'; ?>
<?php if ($s['type'] === 'text'): ?>
<section class="<?= $bg ?> py-16">
  <div class="max-w-4xl mx-auto px-4 prose-mono">
    <h2><?= e($s['heading']) ?></h2>
    <?php foreach ($s['paragraphs'] ?? [] as $p): ?><p><?= e($p) ?></p><?php endforeach; ?>
    <?php if (!empty($s['list'])): ?>
    <ul class="check-list"><?php foreach ($s['list'] as $li): ?><li><?= e($li) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <?php foreach ($s['after'] ?? [] as $p): ?><p><?= e($p) ?></p><?php endforeach; ?>
  </div>
</section>

<?php elseif ($s['type'] === 'services'): ?>
<section class="<?= $bg ?> py-16">
  <div class="max-w-6xl mx-auto px-4">
    <h2 class="section-title text-center"><?= e($s['heading']) ?></h2>
    <?php if (!empty($s['intro'])): ?><p class="mt-4 max-w-3xl mx-auto text-center text-ink-soft leading-relaxed"><?= e($s['intro']) ?></p><?php endif; ?>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mt-10">
      <?php foreach ($s['items'] as $it): ?>
      <div class="card-mono">
        <h3 class="text-lg font-bold"><?= e($it['title']) ?></h3>
        <div class="w-10 h-0.5 bg-accent mt-2"></div>
        <p class="mt-3 text-sm text-ink-soft leading-relaxed"><?= e($it['text']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php elseif ($s['type'] === 'process'): ?>
<section class="<?= $bg ?> py-16">
  <div class="max-w-4xl mx-auto px-4 prose-mono">
    <h2><?= e($s['heading']) ?></h2>
    <?php if (!empty($s['intro'])): ?><p><?= e($s['intro']) ?></p><?php endif; ?>
    <ol class="steps">
      <?php foreach ($s['steps'] as $st): ?><li><strong><?= e($st['title']) ?></strong> — <?= e($st['text']) ?></li><?php endforeach; ?>
    </ol>
  </div>
</section>

<?php elseif ($s['type'] === 'case-studies'):
    $cases = array_filter($s['items'], fn($c) => empty($c['placeholder']));
    if (!$cases) continue; ?>
<section class="<?= $bg ?> py-16">
  <div class="max-w-6xl mx-auto px-4">
    <h2 class="section-title text-center"><?= e($s['heading']) ?></h2>
    <div class="grid md:grid-cols-2 gap-6 mt-10">
      <?php foreach ($cases as $c): ?>
      <div class="card-mono">
        <h3 class="text-lg font-bold"><?= e($c['client']) ?></h3>
        <p class="mt-3 text-sm text-ink-soft leading-relaxed"><strong>Challenge:</strong> <?= e($c['challenge']) ?></p>
        <p class="mt-2 text-sm text-ink-soft leading-relaxed"><strong>What we did:</strong> <?= e($c['work']) ?></p>
        <p class="mt-2 text-sm text-ink-soft leading-relaxed"><strong>Result:</strong> <?= e($c['result']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php elseif ($s['type'] === 'faq'): ?>
<section class="<?= $bg ?> py-16">
  <div class="max-w-4xl mx-auto px-4">
    <h2 class="section-title text-center"><?= e($s['heading']) ?></h2>
    <div class="space-y-4 mt-10">
      <?php foreach ($s['items'] as $qa): ?>
      <div class="bg-white rounded-2xl shadow-sm border border-line/60 overflow-hidden">
        <button type="button" data-faq class="w-full flex justify-between items-center gap-4 p-5 text-left font-semibold hover:text-accent-dark transition-colors">
          <h3 class="text-base font-semibold"><?= e($qa['q']) ?></h3>
          <span class="faq-icon shrink-0 w-8 h-8 rounded-full bg-accent-soft text-accent-dark flex items-center justify-center text-xl leading-none font-bold">+</span>
        </button>
        <div class="hidden px-5 pb-5 text-sm text-ink-soft leading-relaxed border-t border-line/60 pt-4"><?= e($qa['a']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php endforeach; ?>

<?php $relRoute = $svcRoute; $relServices = $data['related'] ?? []; require __DIR__ . '/service-related.php'; ?>

<!-- CTA -->
<section class="hero-grad text-white py-16 relative overflow-hidden">
  <div class="absolute inset-0 hero-dots opacity-30"></div>
  <div class="max-w-4xl mx-auto px-4 text-center relative">
    <h2 class="text-3xl md:text-4xl font-bold drop-shadow"><?= e($data['cta']['heading']) ?></h2>
    <p class="mt-3 text-white/90"><?= e($data['cta']['text']) ?></p>
    <a href="/contact-us" class="btn-light mt-8">Contact Us</a>
  </div>
</section>

<?php require __DIR__ . '/footer.php'; ?>
