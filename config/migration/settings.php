<?php

/*
|--------------------------------------------------------------------------
| Site settings sourced from Joomla
|--------------------------------------------------------------------------
|
| Everything an editor might change that used to be locked inside
| configuration.php or the Helix Ultimate template params. Each entry becomes
| a `settings` row managed on the Filament Settings page.
|
| `from` is either:
|   helix:<param>    a key in a0cbr_template_styles.params (style id 12)
|   const:<value>    a decided constant (see MIGRATION-PLAN.md §12)
|
*/

return [
    'source' => 'a0cbr_template_styles',
    'template_style_id' => 12,          // shaper_helixultimate - Default

    'settings' => [

        // --- identity -----------------------------------------------------
        [
            'key' => 'site_name', 'group' => 'identity', 'type' => 'text',
            'label' => 'Site name', 'ordering' => 10,
            // Legacy configuration.php said "DIU", which is the parent
            // university, not this institute. Corrected per approved plan §12.
            'from' => 'const:Daffodil Institute of Languages & Culture',
        ],
        [
            'key' => 'site_short_name', 'group' => 'identity', 'type' => 'text',
            'label' => 'Short name', 'ordering' => 20,
            'from' => 'const:DILC',
        ],
        [
            'key' => 'parent_org', 'group' => 'identity', 'type' => 'text',
            'label' => 'Parent organisation', 'ordering' => 30,
            'from' => 'const:Daffodil International University',
        ],
        [
            'key' => 'tagline', 'group' => 'identity', 'type' => 'text',
            'label' => 'Tagline', 'ordering' => 40,
            'from' => 'const:Language education, cultural understanding and global engagement',
        ],
        [
            'key' => 'logo', 'group' => 'identity', 'type' => 'image',
            'label' => 'Logo', 'ordering' => 50, 'from' => 'helix:logo_image',
        ],
        [
            'key' => 'logo_mobile', 'group' => 'identity', 'type' => 'image',
            'label' => 'Mobile logo', 'ordering' => 60, 'from' => 'helix:mobile_logo',
        ],
        [
            'key' => 'favicon', 'group' => 'identity', 'type' => 'image',
            'label' => 'Favicon', 'ordering' => 70, 'from' => 'helix:favicon',
        ],
        [
            'key' => 'copyright', 'group' => 'identity', 'type' => 'text',
            'label' => 'Copyright line', 'ordering' => 80,
            'description' => 'Use {year} for the current year.',
            // Legacy read "© {year} DIU Press." — wrong organisation, corrected.
            'from' => 'const:© {year} Daffodil Institute of Languages & Culture. All Rights Reserved.',
        ],

        // --- contact ------------------------------------------------------
        [
            'key' => 'contact_email', 'group' => 'contact', 'type' => 'email',
            'label' => 'Email', 'ordering' => 10, 'from' => 'helix:contact_email',
        ],
        [
            'key' => 'contact_phone', 'group' => 'contact', 'type' => 'text',
            'label' => 'Phone', 'ordering' => 20, 'from' => 'helix:contact_mobile',
        ],
        [
            'key' => 'contact_address', 'group' => 'contact', 'type' => 'textarea',
            'label' => 'Address', 'ordering' => 30,
            // Taken from the legacy Contact article body (article id 5).
            'from' => "const:213 R, 1st Floor, Food Court\nDaffodil Smart City (DSC)\nDaffodil International University\nBirulia, Savar, Dhaka-1216",
        ],
        [
            'key' => 'map_embed', 'group' => 'contact', 'type' => 'html',
            'label' => 'Google Maps embed', 'ordering' => 40,
            'description' => 'Full <iframe> embed code.',
            'from' => 'sppb:map',
        ],

        // --- social (all five were "#" placeholders in Helix; imported as
        //     NULL so the footer renders no dead icons) ---------------------
        ['key' => 'social_facebook', 'group' => 'social', 'type' => 'url', 'label' => 'Facebook', 'ordering' => 10, 'from' => 'helix:facebook'],
        ['key' => 'social_linkedin', 'group' => 'social', 'type' => 'url', 'label' => 'LinkedIn', 'ordering' => 20, 'from' => 'helix:linkedin'],
        ['key' => 'social_instagram', 'group' => 'social', 'type' => 'url', 'label' => 'Instagram', 'ordering' => 30, 'from' => 'helix:instagram'],
        ['key' => 'social_youtube', 'group' => 'social', 'type' => 'url', 'label' => 'YouTube', 'ordering' => 40, 'from' => 'helix:youtube'],
        ['key' => 'social_pinterest', 'group' => 'social', 'type' => 'url', 'label' => 'Pinterest', 'ordering' => 50, 'from' => 'helix:pinterest'],

        // --- theme --------------------------------------------------------
        // These feed CSS custom properties, which in turn drive the Tailwind
        // colour scales remapped in @theme. A rebrand is a settings change.
        [
            'key' => 'color_primary', 'group' => 'theme', 'type' => 'color',
            'label' => 'Primary', 'ordering' => 10,
            // Dominant colour of images/dil-logo.png (48.5% of opaque pixels).
            'from' => 'const:#0060a8',
        ],
        [
            'key' => 'color_secondary', 'group' => 'theme', 'type' => 'color',
            'label' => 'Deep navy', 'ordering' => 20,
            // Helix custom_css footer background.
            'from' => 'const:#17469E',
        ],
        [
            'key' => 'color_accent', 'group' => 'theme', 'type' => 'color',
            'label' => 'Accent green', 'ordering' => 30,
            'from' => 'const:#189030',
        ],
        [
            'key' => 'color_link', 'group' => 'theme', 'type' => 'color',
            'label' => 'Link', 'ordering' => 40, 'from' => 'helix:link_color',
        ],
        [
            'key' => 'color_footer_bg', 'group' => 'theme', 'type' => 'color',
            'label' => 'Footer background', 'ordering' => 50,
            'from' => 'const:#17469E',
        ],

        // --- SEO ----------------------------------------------------------
        [
            'key' => 'meta_description', 'group' => 'seo', 'type' => 'textarea',
            'label' => 'Default meta description', 'ordering' => 10,
            'from' => 'const:Daffodil Institute of Languages & Culture (DILC) offers seven foreign languages, IELTS preparation, mock tests, Remedial English and short courses in English at Daffodil International University.',
        ],
        [
            'key' => 'og_image', 'group' => 'seo', 'type' => 'image',
            'label' => 'Default share image', 'ordering' => 20, 'from' => 'const:',
        ],
        [
            'key' => 'analytics_id', 'group' => 'seo', 'type' => 'text',
            'label' => 'Google Analytics ID', 'ordering' => 30,
            'description' => 'e.g. G-XXXXXXXXXX. Leave blank to disable.',
            'from' => 'const:',
        ],
        [
            'key' => 'robots', 'group' => 'seo', 'type' => 'text',
            'label' => 'Robots directive', 'ordering' => 40,
            'from' => 'const:index, follow',
        ],
        [
            'key' => 'header_scripts', 'group' => 'seo', 'type' => 'html',
            'label' => 'Header scripts', 'ordering' => 50,
            'description' => 'Raw HTML/JS, e.g. Search Console verification or Tag Manager. Inserted just before </head>.',
            'from' => 'const:',
        ],
        [
            'key' => 'footer_scripts', 'group' => 'seo', 'type' => 'html',
            'label' => 'Footer scripts', 'ordering' => 60,
            'description' => 'Raw HTML/JS, e.g. chat widgets or pixels. Inserted just before </body>.',
            'from' => 'const:',
        ],

        // --- features -----------------------------------------------------
        [
            'key' => 'show_news', 'group' => 'features', 'type' => 'boolean',
            'label' => 'Show News section', 'ordering' => 10,
            'description' => 'Both legacy news articles were trashed, so this starts off.',
            'from' => 'const:0',
        ],
        [
            'key' => 'contact_form_slug', 'group' => 'features', 'type' => 'text',
            'label' => 'Contact page form', 'ordering' => 20, 'from' => 'const:contact',
        ],
        [
            'key' => 'enquiry_form_slug', 'group' => 'features', 'type' => 'text',
            'label' => 'Course enquiry form', 'ordering' => 30, 'from' => 'const:course-enquiry',
        ],
        [
            'key' => 'enrolment_form_slug', 'group' => 'features', 'type' => 'text',
            'label' => 'Course enrolment form', 'ordering' => 40, 'from' => 'const:enrolment',
            'description' => 'The form shown at the bottom of every course page.',
        ],

        // --- payments -----------------------------------------------------
        [
            'key' => 'payment_info', 'group' => 'payments', 'type' => 'richtext',
            'label' => 'How to pay', 'ordering' => 10, 'from' => 'const:',
            'description' => 'Payment instructions (bKash, bank transfer, …) shown on every course page after the lesson breakdown. Leave blank to hide the section.',
        ],
    ],
];
