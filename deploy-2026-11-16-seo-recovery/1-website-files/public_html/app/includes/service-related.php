<?php
// "Related" block for a main service page: blog posts on the topic, the same service in
// the kept cities (config/kept-cities.php) and closely related services.
// Expects $relRoute (e.g. '/local-seo'); optional $relServices (route => name).
require_once __DIR__ . '/services-config.php';

$relIndex = service_pages_index();
$relSlug = ltrim($relRoute, '/');

// Blog posts whose title matches this service's keywords (most recent first)
$relPosts = [];
$relKeywords = $relIndex[$relRoute][1] ?? [];
if ($relKeywords) {
    $where = implode(' OR ', array_fill(0, count($relKeywords), 'LOWER(title) LIKE ?'));
    $stmt = $conn->prepare("SELECT title, slug FROM posts WHERE " . posts_live_sql() . " AND ($where) ORDER BY date DESC, id DESC LIMIT 40");
    if ($stmt) {
        $likes = array_map(fn($k) => '%' . $k . '%', $relKeywords);
        $stmt->bind_param(str_repeat('s', count($likes)), ...$likes);
        $stmt->execute();
        // Keep only posts this page "owns", so one post is not listed under five services
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $p) {
            $owner = service_page_for_post($p['title']);
            if ($owner && $owner[0] === $relRoute) $relPosts[] = $p;
            if (count($relPosts) >= 4) break;
        }
    }
}

// Kept city pages for this service (only URLs that render, never a redirect)
$relCities = [];
if (isset(services_list()[$relSlug])) {
    $isLive = city_link_filter($conn, [$relSlug], array_keys(kept_cities()));
    foreach (kept_cities() as $cs => $cn) if ($isLive($relSlug, $cs)) $relCities[$cs] = $cn;
}
$relServices = $relServices ?? [];
?>
<?php if ($relPosts || $relCities || $relServices): ?>
<section class="bg-mist py-16">
  <div class="max-w-6xl mx-auto px-4 grid md:grid-cols-3 gap-8">
    <?php if ($relPosts): ?>
    <div>
      <h2 class="text-xl font-bold">Guides from our blog</h2>
      <div class="w-16 h-1 bg-accent rounded-full mt-3 mb-5"></div>
      <ul class="space-y-2 text-sm">
        <?php foreach ($relPosts as $p): ?>
        <li><a href="/blog/<?= e($p['slug']) ?>" class="text-accent-dark hover:underline"><?= e($p['title']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
    <?php if ($relCities): ?>
    <div>
      <h2 class="text-xl font-bold"><?= e($relIndex[$relRoute][0] ?? 'This service') ?> by city</h2>
      <div class="w-16 h-1 bg-accent rounded-full mt-3 mb-5"></div>
      <ul class="grid grid-cols-2 gap-2 text-sm">
        <?php foreach ($relCities as $cs => $cn): ?>
        <li><a href="/<?= e($relSlug) ?>-<?= e($cs) ?>" class="text-accent-dark hover:underline"><?= e($cn) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
    <?php if ($relServices): ?>
    <div>
      <h2 class="text-xl font-bold">Related services</h2>
      <div class="w-16 h-1 bg-accent rounded-full mt-3 mb-5"></div>
      <ul class="space-y-2 text-sm">
        <?php foreach ($relServices as $route => $name): ?>
        <li><a href="<?= e($route) ?>" class="text-accent-dark hover:underline"><?= e($name) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>
