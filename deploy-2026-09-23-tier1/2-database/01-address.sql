-- TopWebExpert official address update (2026-09-23). Same result as app/scripts/migrate-official-address.php.
-- Import FIRST (before 02-tiers.sql). Safe to import twice.
SET NAMES utf8mb4;

-- 1. New head office branch (added only once)
INSERT INTO branches (name, address, phone, email, is_primary, is_active, sort_order)
SELECT 'Head Office - Gurugram',
       'Tower B1, Spaze I-Tech Park, 32B & 33A, Badshahpur Sohna Rd, Sector 49, Gurugram, Haryana 122018',
       '+91 9050126213', 'info@topwebexpert.com', 0, 1, 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM branches WHERE address LIKE '%Spaze%');

-- 2. Make it the only primary branch; turn off the old Noida and Sapphire 83 branches
--    (they can be switched back on in Admin > Branches)
UPDATE branches SET is_primary = 0;
UPDATE branches SET is_primary = 1, is_active = 1, sort_order = 0 WHERE address LIKE '%Spaze%';
UPDATE branches SET is_active = 0
WHERE address NOT LIKE '%Spaze%' AND (name = 'Noida' OR address LIKE '%Noida%' OR address LIKE '%Sapphire 83%');

-- 3. Site settings (address + structured address for Google schema + X profile)
INSERT INTO settings (skey, svalue) VALUES
  ('address', 'Tower B1, Spaze I-Tech Park, 32B & 33A, Badshahpur Sohna Rd, Sector 49, Gurugram, Haryana 122018'),
  ('address_locality', 'Gurugram'),
  ('address_region', 'Haryana'),
  ('postal_code', '122018')
ON DUPLICATE KEY UPDATE svalue = VALUES(svalue);
INSERT INTO settings (skey, svalue) VALUES ('twitter', 'https://x.com/TopWebexpe62866')
ON DUPLICATE KEY UPDATE svalue = IF(svalue = '', VALUES(svalue), svalue);
UPDATE settings SET svalue = REPLACE(svalue, 'Noida', 'Gurugram') WHERE skey = 'footer_about';

-- 4. SEO Manager titles / descriptions / keywords: Noida -> Gurugram
UPDATE page_seo SET meta_title = REPLACE(REPLACE(meta_title, 'Noida', 'Gurugram'), 'noida', 'gurugram') WHERE meta_title LIKE '%noida%';
UPDATE page_seo SET meta_description = REPLACE(REPLACE(meta_description, 'Noida', 'Gurugram'), 'noida', 'gurugram') WHERE meta_description LIKE '%noida%';
UPDATE page_seo SET meta_keywords = REPLACE(REPLACE(meta_keywords, 'Noida', 'Gurugram'), 'noida', 'gurugram') WHERE meta_keywords LIKE '%noida%';

-- Check: SELECT id, name, is_primary, is_active FROM branches;
--   -> "Head Office - Gurugram" is_primary = 1; Noida and Sapphire 83 is_active = 0
