<?php
// Business name, address and phone (NAP) — the ONE source for the header, footer,
// contact page and structured data (SEO recovery, October 2026). Taken from the Contact
// page; must match the Google Business Profile character for character.
//
// While this file exists it replaces Admin > Branches for the public site. Delete or
// rename it to go back to the branches table.
//
// TODO(opening hours): no opening hours exist anywhere on the site. When confirmed, add
// e.g. 'hours' => ['Mo-Fr 10:00-19:00', 'Sa 10:00-15:00'] to a location and it will be
// output as openingHours in the schema.
return [
    'name' => 'TopWebExpert',
    'email' => 'info@topwebexpert.com',
    'locations' => [
        [
            'name' => 'Head Office - Gurugram',
            'primary' => true,
            'street' => 'TOWER B1, Spaze I Tech Park, 32B & 33A, Badshahpur Sohna Rd, Sector 49',
            'locality' => 'Gurugram',
            'region' => 'Haryana',
            'postal_code' => '122018',
            'country' => 'India',
            'country_code' => 'IN',
            'phone' => '+91 9050126213',
            'email' => 'info@topwebexpert.com',
        ],
        [
            'name' => 'Branch - Noida',
            'street' => 'G42, Sector 20',
            'locality' => 'Noida',
            'region' => 'Uttar Pradesh',
            'postal_code' => '201301',
            'country' => 'India',
            'country_code' => 'IN',
            'phone' => '+91 9050126213',
            'email' => 'info@topwebexpert.com',
        ],
        [
            'name' => 'Branch - Australia',
            'street' => '18 Toongabbie Pl',
            'locality' => 'Craigieburn',
            'region' => 'Victoria',
            'postal_code' => '3064',
            'country' => 'Australia',
            'country_code' => 'AU',
            'phone' => '+61 431 006 392',
            'email' => 'info@topwebexpert.com',
        ],
    ],
];
