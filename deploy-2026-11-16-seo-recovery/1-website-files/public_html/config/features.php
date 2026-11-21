<?php
// Feature switches (SEO recovery, October 2026).
return [
    // Bulk generation of /{service}-{city} pages and city guides (Admin > City Pages >
    // Generate, "Run Shopify keyword expansion", app/scripts/migrate-shopify-keywords.php).
    // Mass-produced location pages are what Google's scaled-content demotion targeted in
    // August 2026; keep this off. Single pages can still be edited in Admin > City Pages.
    'programmatic_generation' => false,
];
