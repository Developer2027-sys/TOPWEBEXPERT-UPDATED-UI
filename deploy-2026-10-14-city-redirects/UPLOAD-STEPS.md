# Deploy — City pages redirect (14 Oct 2026)

Kya hota hai: saare guide × city articles (`/common-seo-mistakes-to-avoid-delhi` jaise) aur saare **Tier 3** service × city pages ab **301 redirect** hote hain apni service ke main page par. Tier 1/2 pages (Google mein index) waise hi chalte rahenge. Database mein koi change nahi.

```
deploy-2026-10-14-city-redirects/
└── 1-website-files/public_html/app/
    ├── router.php
    ├── city-page.php
    ├── shopify-services.php
    └── includes/services-config.php
```

## Step 0 — Backup (zaroori)
hPanel → File Manager → `public_html/app/` se ye 4 files **download** karke kahin save kar lein:
- `app/router.php`
- `app/city-page.php`
- `app/shopify-services.php`
- `app/includes/services-config.php`

## Step 1 — Check (phpMyAdmin → SQL tab)
```sql
SELECT tier, COUNT(*) FROM city_pages GROUP BY tier;
```
Tier 1 / 2 / 3 ki counts aani chahiye. Error aaye ("Unknown column 'tier'") to **ruk jayein**: pehle `deploy-2026-09-23-tier1` wala `02-tiers.sql` import karein. (Bina tier column ke sirf guide pages redirect honge, service × city pages nahi.)

## Step 2 — Upload
`1-website-files/public_html/` ke andar ka `app/` folder server ke `public_html` mein upload karein (same structure, **overwrite**). Sirf ye 4 files badlengi.

## Step 3 — Test (browser, ya https://httpstatus.io par)
| URL | Expected |
|---|---|
| `https://topwebexpert.com/common-seo-mistakes-to-avoid-delhi` | 301 → `/search-engine-optimization` |
| `https://topwebexpert.com/why-content-marketing-matters-mumbai` | 301 → `/content-marketing` |
| `https://topwebexpert.com/search-engine-optimization-itarsi` | 301 → `/search-engine-optimization` (Tier 3) |
| `https://topwebexpert.com/logo-design-itarsi` | 301 → `/graphic-designing` (Tier 3) |
| `https://topwebexpert.com/search-engine-optimization-delhi` | 200, page khulta hai (Tier 1) |
| `https://topwebexpert.com/local-seo-gurgaon` | 200, page khulta hai (Tier 1) |
| `https://topwebexpert.com/shopify-services` | 200; services ke neeche countries ab link nahi, plain text (wo pages Tier 3 hain) |
| `https://topwebexpert.com/` , `/blogs` , `/our-services` | 200 |

Kuch bhi galat lage (500 error, blank page) → turant Step 0 wali 4 files wapas upload kar dein.

## Step 4 — Baad mein
- Search Console mein kuch submit karne ki zaroorat nahi. Google agle crawl mein redirects dekh lega.
- 2–3 mahine "Pages" report dekhte rahein: "Page with redirect" count badhega — ye normal hai.
- Database se city pages **delete na karein**: redirect inhi rows se decide hota hai. Delete karne par URLs 301 ki jagah 404 denge.

## Redirect mapping
| Service | Redirect |
|---|---|
| SEO, Local SEO, Google My Business | `/search-engine-optimization` |
| SMO, Instagram, LinkedIn, YouTube, Influencer | `/social-media-optimization` |
| Facebook Ads | `/facebook-marketing` |
| Google Ads | `/google-marketing` |
| PPC, Lead Generation, Real Estate | `/pay-per-click` |
| Content, Email | `/content-marketing` |
| Web Dev, WordPress, Landing Page, App, Software | `/web-development-company` |
| E-commerce | `/ecommerce-website-development-company` |
| Saari Shopify services | `/shopify-services` |
| Website Maintenance | `/website-maintenance-services` |
| Graphic, Logo, UI/UX | `/graphic-designing` |
| SMS / WhatsApp | `/sms-marketing` / `/whatsapp-marketing` |
| Online Reputation Management | `/our-services` |

## Wapas lena ho to
Step 0 ki 4 backup files upload kar dein. Sab pages pehle jaise.
