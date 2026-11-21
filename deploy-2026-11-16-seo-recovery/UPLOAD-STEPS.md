# Deploy — SEO recovery (16 Nov 2026)

## Kya badlega
21 Aug 2026 ko Google ne poori site ko neeche gira diya tha. Wajah: ~3.18 lakh auto-generated pages (service × city aur guide × city) aur ~1,000 template wale blog posts. Is deploy ke baad Google ko sirf asli pages dikhenge.

| | Abhi live | Deploy ke baad |
|---|---|---|
| Sitemap URLs | 3,18,612 | **63** (25 main pages + 8 blogs + 30 city pages) |
| Purane `sitemap-guides-*` / `sitemap-cities-*` | 200 | **410 Gone** |
| Purane WordPress sitemaps | — | 301 → `/sitemap.xml` |
| Baaki duniya ke city pages (`/local-seo-perth`) | 200 | **301** → service page (`/local-seo`) |
| Guide pages | 301 | 301 / 410 (pehle jaisa) |
| Indexable city pages | ~93,000 | **30**: Gurugram, Noida, Delhi × 10 core services |
| 14 aur kept cities (Mumbai, Pune, Melbourne…) | index | page khulta hai, lekin `noindex, follow`, sitemap mein nahi |
| Blog posts | 641 live + 410 scheduled (roz 1 naya) | **8 asli posts**. Baaki 1,043 → **410 Gone**, scheduled kabhi live nahi honge |
| `www.topwebexpert.com` | 200 (duplicate) | 301 → `topwebexpert.com` |
| Naye hub pages | 404 | `/local-seo`, `/google-my-business`, `/app-development`, `/ui-ux-design` |

Database se **kuch bhi delete nahi hota**. Blog aur city ke rows waise hi rehte hain. Kya dikhana hai, yeh `config/kept-blogs.php` aur `config/kept-cities.php` decide karte hain.

```
deploy-2026-11-16-seo-recovery/
└── 1-website-files/public_html/   (44 files + app/scripts/data/README.txt)
    ├── .htaccess, robots.txt, llms.txt, sitemap.php
    ├── config/   business.php, features.php, kept-blogs.php (naya), kept-cities.php
    └── app/      router.php, city-page.php, blog.php, blogs.php, includes/…, content/…, scripts/…
```

## Step 0 — Backup (zaroori)
1. hPanel → File Manager → `public_html` → **poora `public_html` folder Compress (zip) karke download** kar lein. Kaafi files badal rahi hain, isliye poora backup lena aasaan hai.
2. phpMyAdmin → database `u224219320_topwebmain` → **Export** (Quick, SQL) karke download kar lein.

## Step 1 — Check (phpMyAdmin → SQL tab)
```sql
SELECT id, slug FROM posts WHERE id BETWEEN 79 AND 86 ORDER BY id;
SELECT MAX(id) FROM posts;
```
- Pehli query mein **8 rows** aani chahiye, `real-estate-website-designing-agency` (79) se `how-to-improve-your-websites-seo` (86) tak.
- `MAX(id)` **1132** hona chahiye. Agar zyada hai, toh kisi ne 16 Sep ke baad naye posts jode hain. Woh posts live rahenge, lekin agar woh bhi template wale hain toh ruk jaiye aur batayiye.
- Kuch alag aaye toh **ruk jaiye**. `config/kept-blogs.php` badalna padega.

## Step 2 — Address / phone check
`config/business.php` ka address aur phone ab header, footer, contact page aur Google ke structured data mein jayega:
- Gurugram: TOWER B1, Spaze I Tech Park, Sector 49, Gurugram 122018, +91 9050126213
- Noida: G42, Sector 20, Noida 201301
- Australia: 18 Toongabbie Pl, Craigieburn VIC 3064, +61 431 006 392

Yeh **Google Business Profile se bilkul same** hona chahiye. Galat ho toh upload se pehle file mein theek kar lein.

## Step 3 — Upload
`1-website-files/public_html/` ke andar ka sab kuch server ke `public_html` mein upload karein (same structure, **overwrite**). `.htaccess` ek hidden file hai. File Manager mein "Show hidden files" on karke check kar lein ki woh bhi upload hui.

## Step 4 — Database step (SSH)
hPanel → Advanced → SSH Access se login karke:
```bash
cd ~/public_html          # ya jahan site hai, e.g. ~/domains/topwebexpert.com/public_html
php app/scripts/migrate-seo-recovery.php --check   # sirf dikhata hai kya badlega
php app/scripts/migrate-seo-recovery.php           # apply
```
Yeh script ye kaam karti hai:
- Purane SEO Manager titles hatati hai ("#1 SEO Company in Noida" jaise).
- Settings aur Branches ko `config/business.php` ke address se match karti hai.
- Blog aur city pages ke andar ke links jo redirect par jaate hain, unhe theek karti hai.
- Sitemap cache saaf karti hai.

Pehle ka sab data `app/scripts/data/backup-seo-recovery-*.json` mein save ho jata hai.
### SSH nahi hai? phpMyAdmin se (same result)
`2-database/01-seo-recovery.sql` script ke step 1 + 2 jaisa hi kaam karti hai: purane SEO titles saaf karna, aur address/footer settings. Yeh file ek test database par chala kar check ki gayi hai.
1. phpMyAdmin → `u224219320_topwebmain` → **Export** → "Custom" → sirf tables **`page_seo`** aur **`settings`** select karein → Go. Yeh backup hai.
2. Database select karke **Import** tab → `01-seo-recovery.sql` choose karein → Go.
   (Ya **SQL** tab mein file ka poora text paste karke Go.)
3. Neeche 2 result tables aayengi: settings mein Gurugram address aur naya footer text, aur `page_seo` mein `/` aur `/search-engine-optimization` ke title/description **khaali**. Khaali hona sahi hai, ab title code se aata hai.

SQL file blog ya city pages ke andar ke links nahi badalti (script ka step 4). Yeh links 301 se sahi page par pahunch jaate hain, isliye zaroori nahi hai.

### Footer link (chhota fix)
Live `app/includes/footer.php` purana hai, isliye footer mein "Shopify Services" ka link nahi hai. `3-footer-fix/public_html/app/includes/footer.php` ko server ke `public_html/app/includes/` mein upload (overwrite) kar dein.

## Step 4b — CDN cache purge (zaroori)
Site Hostinger CDN (`hcdn`) ke peeche hai. hPanel → Website → **CDN → Purge all cache**.
Yeh nahi kiya toh Google aur visitors ko kuch din tak purana sitemap aur purane pages milte rahenge, aur Step 5 ke test bhi galat nateeja denge.

## Step 5 — Test (browser, ya https://httpstatus.io)
| URL | Expected |
|---|---|
| `https://topwebexpert.com/sitemap.xml` | 200, sirf 3 sitemaps (pages, blogs, local) |
| `https://topwebexpert.com/sitemap-local.xml` | 30 URLs (Gurugram/Noida/Delhi) |
| `https://topwebexpert.com/sitemap-blogs.xml` | 8 URLs |
| `https://topwebexpert.com/sitemap-guides-1.xml` | **410** |
| `https://topwebexpert.com/local-seo-perth` | **301 → /local-seo** |
| `https://topwebexpert.com/search-engine-optimization-delhi` | 200, `index, follow` |
| `https://topwebexpert.com/search-engine-optimization-mumbai` | 200, page source mein `noindex, follow` |
| `https://topwebexpert.com/blog/the-complete-guide-to-local-seo` | **410** |
| `https://topwebexpert.com/blog/how-to-improve-your-websites-seo` | 200 |
| `https://www.topwebexpert.com/` | **301 → https://topwebexpert.com/** |
| `/`, `/blogs`, `/our-services`, `/local-seo`, `/contact-us`, `/admin` | 200 / login page |
| Contact form se ek test enquiry | Admin → Inquiries mein aani chahiye |

Kuch bhi galat lage (500 error, blank page): Step 0 wala zip wapas upload kar dein.

## Step 6 — Google Search Console (deploy ke din hi)
1. **Sitemaps** → purane sab sitemaps **Remove** karein: `sitemap-guides-1…23`, `sitemap-cities-1…5`, `sitemap-blogs.xml`, `sitemap-pages.xml`, `page-sitemap.xml`, `category-sitemap.xml`, `post-sitemap.xml`. Har ek par ⋮ → Remove sitemap.
2. Sirf **`sitemap.xml`** rehne dein, aur use dobara **Submit** karein.
3. **URL Inspection** → `https://topwebexpert.com/` aur 3–4 main service pages → "Request indexing".
4. **Pages** report → "Crawled – currently not indexed" aur "Page with redirect" par validation **mat** chalaiye. Yeh counts jaan-boojhkar badhenge aur phir dheere-dheere ghatenge.
5. **Removals** tool purane URLs ke liye **use mat kijiye**. 410/301 apne aap kaam karenge. Removals sirf 6 mahine ke liye chhupata hai.

## Step 7 — Code ke bahar ke kaam
- **Google Business Profile:** address, phone aur website link `config/business.php` (site footer) se bilkul same karein.
- **Bing Webmaster Tools** (agar account hai): wahan bhi sirf `sitemap.xml` rakhein.
- **Subdomains** (`adscrm.`, `course.`, `hotelrrgalaxy.`, `cosmos.`, `realestate.`) Google index mein hain. CRM ya login wale pages par `noindex` hona chahiye. Yeh unke apne code mein hoga, is deploy mein nahi.
- **Search Console → Pages:** 404 (17), 5xx (4), Soft 404 (2), 403 (1) wale URLs export karke check karwayein.
- **Search Console → Settings → Users and permissions:** jinko access ki zaroorat nahi, unhe hata dein.

## Monitoring
| Kab | Kya dekhna hai |
|---|---|
| 2–3 din | Sitemaps: `sitemap.xml` "Success", ~63 discovered pages |
| 1–2 hafte | Crawl stats mein 410/301 badhein; Pages mein "Not found (410)" aaye |
| Har hafte | Performance (3 months): impressions, Delhi NCR queries |
| 1–3 mahine | Indexed count ~100 ke aas-paas tike |

## Iske baad kya hoga
- Pehle 2–6 hafte: "Page with redirect", "Not found (410)" aur "Excluded by noindex" ke counts badhenge, aur Indexed count ghatega. **Yeh sahi hai.**
- Recovery tab shuru hoti hai jab Google site ke zyada URLs dobara crawl kar le. Aise demotion mein aam taur par kuch mahine lagte hain. Koi tareekh pakki nahi hai.
- Admin → City Pages / Blogs mein **naye bulk pages mat banaiye**. `config/features.php` mein `programmatic_generation` band hai, use band hi rehne dein.
- Naye blog posts Admin → Blogs se normal tarah likhe ja sakte hain. Woh apne aap live honge (id 1132 ke baad wale).

## Wapas lena ho to
Step 0 ka `public_html` zip wapas upload karein. Database ke liye:
`php app/scripts/migrate-seo-recovery.php --restore app/scripts/data/backup-seo-recovery-XXXX.json`
