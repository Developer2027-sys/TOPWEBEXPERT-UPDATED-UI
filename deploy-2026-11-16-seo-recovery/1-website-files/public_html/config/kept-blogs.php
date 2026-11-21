<?php
// Which blog posts are live (SEO recovery, November 2026).
//
// Posts 87 to 1132 were generated in bulk from ~37 templates x 28 services ("The complete
// guide to X", "10 common X mistakes", "X for <industry>: a practical guide" ...), with
// back-dated and future dates so one appeared every day until December 2027. Pairs of
// them share up to ~76% of their text: the same scaled-content pattern as the city pages.
// They now answer 410 Gone, are left out of the blog list, sitemap and related links,
// and the scheduled ones never go live. The rows stay in the database (nothing deleted).
//
// Live posts = the ones listed in keep_ids, plus every post created after
// retired_through_id (so posts written in Admin > Blogs from now on work normally).
// To bring a retired post back after rewriting it by hand, add its id to keep_ids.
return [
    // The original articles from the WordPress site (imported March 2025)
    'keep_ids' => [79, 80, 81, 82, 83, 84, 85, 86],
    // Highest id of the bulk-generated batch
    'retired_through_id' => 1132,
];
