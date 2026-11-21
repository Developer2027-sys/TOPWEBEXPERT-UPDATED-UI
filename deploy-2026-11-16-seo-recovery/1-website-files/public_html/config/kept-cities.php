<?php
// Cities whose /{service}-{city} pages stay live and indexable (SEO recovery, October 2026).
//
// Every other /{service}-{city} URL is a 301 to that service's main page
// (see city_redirect_target() in app/includes/services-config.php). A kept city keeps
// a page only for services that already have a city_pages row for it; nothing new is
// generated from this list.
//
// To add or remove a city: edit the list below (use the slug from Admin > Locations),
// then purge the sitemap cache (php app/scripts/purge-cache.php) so sitemap.xml follows.
//
// Format: 'url-slug' => 'Display name'
return [
    'cities' => [
        // Delhi NCR — head office (Gurugram), branch (Noida) and home market
        'gurgaon' => 'Gurugram',          // slug stays "gurgaon": these URLs are indexed
        'noida' => 'Noida',
        'greater-noida' => 'Greater Noida',
        'delhi' => 'Delhi',
        'new-delhi' => 'New Delhi',
        'ghaziabad' => 'Ghaziabad',
        'faridabad' => 'Faridabad',
        // Major Indian metros
        'mumbai' => 'Mumbai',
        'bangalore' => 'Bangalore',
        'pune' => 'Pune',
        'hyderabad' => 'Hyderabad',
        'chennai' => 'Chennai',
        'kolkata' => 'Kolkata',
        'jaipur' => 'Jaipur',
        'chandigarh' => 'Chandigarh',
        'lucknow' => 'Lucknow',
        // Australia — branch office
        'melbourne' => 'Melbourne',
    ],

    // Alternative spellings: /{service}-{alias} is a 301 to /{service}-{city}
    'aliases' => [
        'gurugram' => 'gurgaon',
        'bengaluru' => 'bangalore',
    ],

    // Which kept city pages Google may INDEX (November 2026). Kept pages share ~60% of
    // their text, so only the home market x core services stays indexable; every other
    // kept page still loads (no redirect) but is "noindex, follow", left out of the
    // sitemap and not linked from other pages. Before adding a page here, give it
    // content of its own (Admin > City Pages > edit): a near-copy only adds to the
    // scaled-content problem.
    'indexable' => [
        'cities' => ['gurgaon', 'noida', 'delhi'],
        'services' => [
            'search-engine-optimization', 'local-seo', 'google-my-business',
            'social-media-optimization', 'pay-per-click', 'google-marketing',
            'facebook-marketing', 'content-marketing', 'web-development-company',
            'ecommerce-website-development-company',
        ],
    ],
];
