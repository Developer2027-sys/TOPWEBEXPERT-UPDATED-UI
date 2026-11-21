# Deploy — Gurugram address + Tier plan + 20 Gurugram pages (23 Sep 2026)

Is folder mein wahi hai jo server par upload karna hai. **Order follow karein.**

```
deploy-2026-09-23-tier1/
├── 1-website-files/public_html/   → hPanel File Manager: public_html mein upload (overwrite)
├── 2-database/                    → phpMyAdmin: Import (01 → 02 → 03 order mein)
└── 3-ssh-scripts-optional/        → sirf agar SSH se chalana ho (2-database ki jagah)
```

## Step 0 — Backup (zaroori)
- hPanel → Databases → phpMyAdmin → database `u224219320_topwebmain` → **Export** (Quick, SQL) → file save karein.
- File Manager mein `public_html` ka zip / backup bana lein (ya hPanel → Backups).

## Step 1 — Database (phpMyAdmin → Import, ek-ek karke, isi order mein)

| # | File | Kya karti hai |
|---|---|---|
| 1 | `2-database/01-address.sql` | Naya head office (Spaze I-Tech Park, Sector 49, Gurugram) primary; Noida + Sapphire 83 branches off; Settings mein address, City/State/PIN, X URL; SEO Manager ke titles mein Noida → Gurugram |
| 2 | `2-database/02-tiers.sql` | `city_pages` mein `tier` column; Tier 1 = 756 pages (12 core cities), Tier 2 = 643 proven pages, baaki Tier 3 |
| 3 | `2-database/03-gurugram-tier1-rewrites.sql` | 20 Gurugram pages ka naya unique content (SEO, Local SEO, GBP, Google Ads, Facebook Ads, Website, E-commerce, WordPress, Shopify, Maintenance, App, Software, UI/UX, Logo, Content, WhatsApp, SMS, Real Estate, Lead Generation, SMO) |

Check (phpMyAdmin → SQL tab):
```sql
SELECT tier, COUNT(*) FROM city_pages GROUP BY tier;   -- 1: 756 | 2: ~643 | 3: baaki
SELECT id, name, is_primary, is_active FROM branches;   -- "Head Office - Gurugram" primary
```
(Tier 2 thoda kam/zyada ho sakta hai agar live par koi page local copy se alag hai — normal hai.)

**SSH ho to** Step 1 ki jagah (3-ssh-scripts-optional ke files `public_html/app/scripts/` mein upload karke):
```
php public_html/app/scripts/migrate-official-address.php
php public_html/app/scripts/migrate-tiers.php
php public_html/app/scripts/import-page-rewrites.php
```
Phir `app/scripts/` folder server se delete kar dein (README ke hisaab se security).

## Step 2 — Website files
`1-website-files/public_html/` ke andar ke saare files/folders server ke `public_html` mein upload karein (same folder structure, **overwrite**):

```
llms.txt
sitemap.php
app/home.php
app/city-page.php
app/guide-page.php
app/includes/header.php
app/includes/seo.php
app/includes/settings.php
app/includes/service-template.php
app/admin/settings.php
app/admin/city-page-edit.php
app/content/home.json, about-us.json, seo.json, contact-us.json, layout.json, our-services.json
```
Database pehle, files baad mein — taaki naya code turant `tier` column dekhe. (Ulta ho jaye to bhi site nahi tootegi; code column na mile to purana behaviour rakhta hai.)

## Step 3 — Site par
1. Admin → Dashboard → **Clear Page Cache** (purana sitemap cache hatega).
2. Admin → Settings: check karein Address / City / State / PIN bhare hain. Agar Google Business Profile ka link, YouTube, Medium hain to unke naye fields mein daal dein.
3. Test (browser → View Page Source):
   - `https://topwebexpert.com/local-seo-gurgaon` → naya content, `<meta name="robots" content="index, follow">`
   - `https://topwebexpert.com/search-engine-optimization-itarsi` → `noindex, follow` (Tier 3 — page khulta hai, bas Google se hidden)
   - `https://topwebexpert.com/sitemap.xml` → sirf pages, blogs, `sitemap-cities-1.xml` (~1,400 city URLs); guides wale sitemap nahi
   - `https://topwebexpert.com/contact-us` → sirf Gurugram head office + Australia

## Step 4 — Search Console
1. Sitemaps → `sitemap.xml` dobara **Submit**. Purane `sitemap-guides-*.xml` / `sitemap_index.xml` list mein hon to remove.
2. URL Inspection → in 20 URLs par **Request Indexing** (roz ~10 ki limit hoti hai, 2 din mein karein):
   `/search-engine-optimization-gurgaon`, `/local-seo-gurgaon`, `/google-my-business-gurgaon`, `/google-marketing-gurgaon`, `/facebook-marketing-gurgaon`, `/web-development-company-gurgaon`, `/ecommerce-website-development-company-gurgaon`, `/wordpress-development-gurgaon`, `/shopify-development-gurgaon`, `/website-maintenance-services-gurgaon`, `/app-development-gurgaon`, `/software-development-gurgaon`, `/ui-ux-design-gurgaon`, `/logo-design-gurgaon`, `/content-marketing-gurgaon`, `/whatsapp-marketing-gurgaon`, `/sms-marketing-gurgaon`, `/real-estate-marketing-gurgaon`, `/lead-generation-gurgaon`, `/social-media-optimization-gurgaon`
3. Homepage `/` par bhi Request Indexing (naya Gurugram address).

## Step 5 — Site ke bahar (utna hi zaroori)
Sab jagah **bilkul same** naam, address, phone:
`TopWebExpert · Tower B1, Spaze I-Tech Park, 32B & 33A, Badshahpur Sohna Rd, Sector 49, Gurugram, Haryana 122018 · +91 90501 26213`
- Google Business Profile: address + description ("Oracle Web Design" hata kar TopWebExpert), category, photos
- Facebook (do pages → ek), Instagram, X, LinkedIn bio
- IndiaMART: category (web design / digital marketing) + city Gurugram

## Upload se pehle confirm karein (20 pages mein likha hai)
- Payment ke baad code/design ka ownership client ka hota hai
- Logo client ko likhit copyright assignment milta hai
- Medical content doctor review karte hain
- Apps sasti Android phones par test hote hain

Inme se koi baat sach nahi hai to batayein — upload se pehle page theek kar dunga.

## Wapas lena ho to
- Tier: `ALTER TABLE city_pages DROP COLUMN tier;` → saare city pages phir se index + sitemap mein. Guide × city pages ke liye purane `app/guide-page.php` aur `sitemap.php` (Step 0 backup) wapas upload karein.
- Address: Admin → Branches se Noida wapas on / primary.
- Content: Step 0 ka backup.
