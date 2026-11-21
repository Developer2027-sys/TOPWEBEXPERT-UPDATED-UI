<?php
// Titles and meta descriptions for the main pages (SEO recovery, October 2026).
// Pattern: keyword + benefit + proof + brand. Titles <= 60 characters, descriptions
// <= 155. Figures used are the site's own: 7+ years, 150+ clients, 500+ projects.
//
// Priority in render_seo(): Admin > SEO Manager override > this table > the page's own.
// scripts/migrate-seo-recovery.php clears the old (Noida-era) overrides for these
// routes, after backing them up, so this table takes effect.
function seo_meta_table() {
    return [
        '/' => ['TopWebExpert | Website & Digital Marketing Agency, Delhi NCR',
            'Delhi NCR web development & digital marketing agency, HQ in Gurugram with an office in Noida. 7+ years, 150+ clients, 500+ projects. Free consultation.'],
        '/about-us' => ['About TopWebExpert | Delhi NCR Agency Since 7+ Years',
            'Meet TopWebExpert: a web development and digital marketing team in Gurugram and Noida that has delivered 500+ projects for 150+ clients.'],
        '/our-services' => ['Digital Marketing & Web Development Services | TopWebExpert',
            'SEO, local SEO, Google Ads, social media, websites, apps, Shopify and UI/UX under one roof. 7+ years, 500+ projects. See every service we offer.'],
        '/our-portfolio' => ['Our Portfolio | Brands We Have Grown – TopWebExpert',
            'Websites, stores and campaigns built for 150+ clients across India and abroad. Browse the brands TopWebExpert has helped grow online.'],
        '/blogs' => ['Digital Marketing & Web Development Blog | TopWebExpert',
            'Practical guides on SEO, Google Ads, social media, websites and e-commerce from the TopWebExpert team in Gurugram and Noida.'],
        '/contact-us' => ['Contact TopWebExpert | Gurugram & Noida Offices',
            'Call +91 9050126213 or visit our Gurugram head office or Noida office. Free consultation on websites, SEO and digital marketing. Reply within 24 hours.'],
        '/shopify-services' => ['Shopify Development Agency | Stores & Apps – TopWebExpert',
            'Shopify store design, theme and app development, Shopify Plus, migrations, SEO and CRO by one team. 7+ years, 500+ projects. Free Shopify consultation.'],
        '/search-engine-optimization' => ['SEO Services | Rank Higher, Get More Leads – TopWebExpert',
            'Technical SEO, on-page, content and link building that bring organic leads, not just rankings. 7+ years, 150+ clients. Get a free SEO audit.'],
        '/social-media-optimization' => ['Social Media Optimization | Grow Your Brand – TopWebExpert',
            'Social media strategy, content and community management that build reach and enquiries on Instagram, Facebook and LinkedIn. 150+ clients served.'],
        '/pay-per-click' => ['PPC Management Services | Leads, Not Clicks – TopWebExpert',
            'PPC campaigns on Google, Meta and LinkedIn managed for cost per lead, with weekly tracking and clear reports. 7+ years, 75K+ leads generated.'],
        '/whatsapp-marketing' => ['WhatsApp Marketing | Campaigns & Automation – TopWebExpert',
            'Personalised WhatsApp campaigns, automation and broadcasts that reach customers instantly and drive replies and sales. Free WhatsApp strategy call.'],
        '/sms-marketing' => ['SMS Marketing Services | Reach Customers Fast – TopWebExpert',
            'Targeted SMS campaigns for offers, reminders and alerts that land in seconds, with opt-in compliance and delivery reports. Free consultation.'],
        '/content-marketing' => ['Content Marketing | Content That Ranks – TopWebExpert',
            'Blogs, website copy and content strategy written to rank on Google and turn readers into leads. 7+ years, 500+ projects. Get a free content plan.'],
        '/web-development-company' => ['Website Development Company | Fast, SEO-Ready – TopWebExpert',
            'Custom websites that load fast, rank well and convert visitors into enquiries. Design, build and launch by one team. 500+ projects delivered.'],
        '/graphic-designing' => ['Graphic Design Services | Logos & Branding – TopWebExpert',
            'Logos, brand identity, social media creatives and print design that make your business look professional and memorable. 150+ clients served.'],
        '/google-marketing' => ['Google Ads Management | More Leads per Rupee – TopWebExpert',
            'Search, Performance Max and YouTube ads managed for lower cost per lead, with conversion tracking and monthly reports. 75K+ leads generated.'],
        '/facebook-marketing' => ['Facebook & Instagram Ads Management | TopWebExpert',
            'Meta ad campaigns on Facebook and Instagram with sharp targeting, creatives and lead forms, optimised for cost per lead. 150+ clients served.'],
        '/ecommerce-website-development-company' => ['E-commerce Website Development | Sell Online – TopWebExpert',
            'E-commerce stores with fast checkout, payments, shipping and SEO built in, on Shopify, WooCommerce or custom code. 500+ projects delivered.'],
        '/website-maintenance-services' => ['Website Maintenance | 24/7 Monitoring & Fixes – TopWebExpert',
            'Updates, backups, security fixes, speed tuning and 24/7 monitoring so your website stays fast, safe and online. Plans for every business.'],
        '/terms-and-conditions' => ['Terms and Conditions | TopWebExpert',
            'The terms that apply when you use the TopWebExpert website or work with us on web development and digital marketing projects.'],
        '/privacy-policy' => ['Privacy Policy | TopWebExpert',
            'How TopWebExpert collects, uses and protects the personal information you share through this website and our enquiry forms.'],
    ];
}

// Title + description for a kept /{service}-{city} page. Replaces the generated
// "X in City | Best X Company in City - TopWebExpert" titles (67-125 characters, all one
// template) with a short keyword + benefit + brand title and a description with proof.
function city_page_seo($serviceSlug, $serviceName, $city) {
    static $short = [
        'search-engine-optimization' => 'SEO Services', 'social-media-optimization' => 'Social Media Marketing',
        'pay-per-click' => 'PPC Services', 'web-development-company' => 'Website Development',
        'ecommerce-website-development-company' => 'E-commerce Development', 'google-my-business' => 'GMB Optimization',
        'google-marketing' => 'Google Ads Management', 'facebook-marketing' => 'Facebook Ads',
        'website-maintenance-services' => 'Website Maintenance', 'online-reputation-management' => 'Reputation Management',
        'app-development' => 'App Development',
    ];
    static $benefit = [
        'search-engine-optimization' => 'Rank Higher on Google', 'local-seo' => 'Rank in Google Maps',
        'google-my-business' => 'More Calls from Maps', 'pay-per-click' => 'Leads, Not Clicks',
        'google-marketing' => 'Lower Cost per Lead', 'facebook-marketing' => 'Ads That Convert',
        'social-media-optimization' => 'Grow Your Brand', 'instagram-marketing' => 'Grow Followers & Sales',
        'linkedin-marketing' => 'B2B Leads', 'youtube-marketing' => 'Grow Your Channel',
        'influencer-marketing' => 'Creators That Sell', 'whatsapp-marketing' => 'Campaigns & Automation',
        'sms-marketing' => 'Reach Customers Fast', 'email-marketing' => 'Campaigns That Convert',
        'content-marketing' => 'Content That Ranks', 'web-development-company' => 'Fast, SEO-Ready Sites',
        'wordpress-development' => 'Fast, Secure Sites', 'landing-page-design' => 'Pages That Convert',
        'ecommerce-website-development-company' => 'Stores That Sell', 'shopify-development' => 'Stores That Sell',
        'app-development' => 'Android & iOS Apps', 'ui-ux-design' => 'Easy-to-Use Design',
        'graphic-designing' => 'Logos & Branding', 'logo-design' => 'Memorable Brands',
        'website-maintenance-services' => 'Updates & Security', 'online-reputation-management' => 'Protect Your Name',
        'lead-generation' => 'Qualified Leads', 'software-development' => 'Custom Solutions',
        'real-estate-marketing' => 'Property Leads',
    ];
    $name = $short[$serviceSlug] ?? $serviceName;
    $b = $benefit[$serviceSlug] ?? 'Free Consultation';
    $title = null;
    foreach (["$serviceName in $city | $b – TopWebExpert", "$name in $city | $b – TopWebExpert",
              "$serviceName in $city – TopWebExpert", "$name in $city – TopWebExpert", "$name $city | TopWebExpert"] as $t) {
        if (mb_strlen($t) <= 60) { $title = $t; break; }
    }
    $title = $title ?? "$name in $city";
    $desc = "$serviceName in $city by TopWebExpert: " . mb_strtolower($b) . '. 7+ years, 150+ clients, 500+ projects. Free consultation.';
    if (mb_strlen($desc) > 155) $desc = "$name in $city by TopWebExpert: " . mb_strtolower($b) . '. 7+ years, 150+ clients. Free consultation.';
    if (mb_strlen($desc) > 155) $desc = mb_substr($desc, 0, 154) . '…';
    return [$title, $desc];
}
