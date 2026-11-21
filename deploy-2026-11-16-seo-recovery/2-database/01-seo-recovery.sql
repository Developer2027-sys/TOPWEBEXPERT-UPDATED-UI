-- SEO recovery (16 Nov 2026): database part, for phpMyAdmin.
-- Same changes as steps 1 and 2 of app/scripts/migrate-seo-recovery.php.
-- Run on database u224219320_topwebmain AFTER exporting the page_seo and settings tables.

SET NAMES utf8mb4;

-- 1. SEO Manager: clear the old meta title / description overrides ("#1 SEO Company in Noida" ...)
--    so the new titles from the code show. Keywords and noindex flags are NOT touched.
UPDATE page_seo SET meta_title = '', meta_description = '' WHERE route IN (
  '/',
  '/about-us',
  '/our-services',
  '/our-portfolio',
  '/blogs',
  '/contact-us',
  '/shopify-services',
  '/search-engine-optimization',
  '/social-media-optimization',
  '/pay-per-click',
  '/whatsapp-marketing',
  '/sms-marketing',
  '/content-marketing',
  '/web-development-company',
  '/graphic-designing',
  '/google-marketing',
  '/facebook-marketing',
  '/ecommerce-website-development-company',
  '/website-maintenance-services',
  '/terms-and-conditions',
  '/privacy-policy'
);

-- 2. Settings: head-office address (same as config/business.php), Delhi NCR footer text, copyright
INSERT INTO settings (skey, svalue) VALUES ('address', 'TOWER B1, Spaze I Tech Park, 32B & 33A, Badshahpur Sohna Rd, Sector 49, Gurugram, Haryana 122018, India') ON DUPLICATE KEY UPDATE svalue = VALUES(svalue);
INSERT INTO settings (skey, svalue) VALUES ('address_locality', 'Gurugram') ON DUPLICATE KEY UPDATE svalue = VALUES(svalue);
INSERT INTO settings (skey, svalue) VALUES ('address_region', 'Haryana') ON DUPLICATE KEY UPDATE svalue = VALUES(svalue);
INSERT INTO settings (skey, svalue) VALUES ('postal_code', '122018') ON DUPLICATE KEY UPDATE svalue = VALUES(svalue);
INSERT INTO settings (skey, svalue) VALUES ('phone', '+91 9050126213') ON DUPLICATE KEY UPDATE svalue = VALUES(svalue);
INSERT INTO settings (skey, svalue) VALUES ('email', 'info@topwebexpert.com') ON DUPLICATE KEY UPDATE svalue = VALUES(svalue);
INSERT INTO settings (skey, svalue) VALUES ('footer_about', 'TopWebExpert is a website development and digital marketing agency for Delhi NCR, headquartered in Gurugram with an office in Noida. Whether you are just starting out or ready to take your digital presence to the next level, we have the expertise and experience to help you succeed.') ON DUPLICATE KEY UPDATE svalue = VALUES(svalue);
INSERT INTO settings (skey, svalue) VALUES ('footer_copyright', '© 2026 TopWebExpert. All rights reserved.') ON DUPLICATE KEY UPDATE svalue = VALUES(svalue);

-- Check: these should now show the new values
SELECT skey, svalue FROM settings WHERE skey IN ('address', 'address_locality', 'address_region', 'postal_code', 'phone', 'email', 'footer_about', 'footer_copyright');
SELECT route, meta_title, meta_description FROM page_seo WHERE route IN ('/', '/search-engine-optimization');
