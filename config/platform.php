<?php

return [

    /*
    | Kernel version. Modules declare "requires_core" against it (SAD Part A §5.2).
    */
    'version' => '1.0.0',

    'modules' => [

        /*
        | Where ZIP-installed modules live. Bundled modules stay in Modules/ (git).
        | Production points this at a shared folder that survives deploys (ADR-12).
        */
        'runtime_path' => (static function (): string {
            $path = env('UWMS_MODULE_RUNTIME_PATH');

            return match (true) {
                ! $path => storage_path('app/modules-runtime'),
                str_starts_with($path, '/') => $path,
                default => base_path($path),
            };
        })(),

        /* Uploaded ZIPs are unpacked here first, validated, then moved. */
        'staging_path' => storage_path('app/module-packages'),

        /* Copies of module folders before update / uninstall. */
        'backup_path' => storage_path('app/module-backups'),

        /* File types allowed inside a module ZIP (FR-MODM-03). */
        'allowed_extensions' => [
            'php', 'json', 'md', 'txt', 'js', 'mjs', 'css', 'scss', 'svg', 'png', 'jpg', 'jpeg', 'gif', 'webp',
            'ico', 'woff', 'woff2', 'ttf', 'eot', 'stub', 'xml', 'yml', 'yaml', 'csv', 'gitkeep', 'lock',
        ],

        /* Largest accepted ZIP, in bytes. */
        'max_zip_size' => 50 * 1024 * 1024,
    ],

    /*
    | Standard Role Catalog defaults (DB-01 §8.4, FR-REL-11). Modules register the
    | keys they use; the label and single-holder flag come from here, and admins
    | may relabel later. Keys never change, so every university shares them.
    | key => [default label, is_single_holder]
    */
    'role_catalog' => [
        'dean' => ['Dean', true],
        'associate_dean' => ['Associate Dean', false],
        'head' => ['Head', true],
        'coordinator' => ['Coordinator', false],
        'officer' => ['Officer', false],
        'industry_expert' => ['Industry Expert', false],
        'contact' => ['Contact Person', false],
        'team' => ['Team', false],
        'responsible' => ['Responsible Person', false],
        'in_charge' => ['In-charge', true],
        'member' => ['Member', false],
        'chairperson' => ['Chairperson', true],
        'convener' => ['Convener', false],
        'member_secretary' => ['Member Secretary', true],
        'holder' => ['Holder', true],
        'speaker' => ['Speaker', false],
        'moderator' => ['Moderator', false],
        'author' => ['Author', false],
        'pi' => ['Principal Investigator', true],
        'co_pi' => ['Co-PI', false],
        'driver' => ['Driver', false],
        'helper' => ['Helper', false],
    ],

    /*
    | Website themes (C-06, SAD Part B §8). Bundled themes live in themes/ (git);
    | ZIP-installed ones in a folder that survives deploys (ADR-12).
    */
    /*
    | FR-BKP-03: database backup before module / theme install. Off in tests
    | (phpunit.xml) — the Backup module's own tests switch it on.
    */
    'backup' => [
        'before_change' => (bool) env('UWMS_BACKUP_BEFORE_CHANGE', true),
    ],

    'themes' => [
        'default' => 'default',                 // BRL-THEME-01: never uninstalled

        'runtime_path' => (static function (): string {
            $path = env('UWMS_THEME_RUNTIME_PATH');

            return match (true) {
                ! $path => storage_path('app/themes-runtime'),
                str_starts_with($path, '/') => $path,
                default => base_path($path),
            };
        })(),
    ],

    /*
    | Admin sign-in (NFR-SEC-03/04). Moves to Website Settings (C-05) later.
    */
    'security' => [
        'login_max_attempts' => 5,          // failed passwords per account …
        'login_lockout_minutes' => 15,      // … then locked this long
        'super_admin_requires_2fa' => (bool) env('UWMS_SUPER_ADMIN_REQUIRES_2FA', false),
        // Invitation and password-reset links share Laravel's broker: config/auth.php passwords.users.expire
    ],

    /*
    | Languages before Localization (C-15) has rows: first install, seeding, tests.
    | The first is the default. Also the admin interface languages (user profile).
    | After install, website languages are managed in Site › Languages.
    */
    'locales' => array_filter(explode(',', env('UWMS_LOCALES', 'en,bn'))),

    /* Main Site pseudo-target in core_relations: ("site", 0) means "published on the main site" (ADR-15). */
    'main_site' => ['type' => 'site', 'id' => 0],

    /*
    | Website addresses (SRS FR-GEN-34/35). A top entity's slug opens its children's URLs
    | ("/fsit/cse"), so it may not be one of the main site's own first segments; inside a
    | microsite, a child's slug may not be one of the microsite's section paths. Locales and
    | microsite route prefixes are added automatically; a main site page or child slug that
    | is taken is checked separately (Platform\Context\UrlRules).
    */
    'reserved_paths' => [
        'admin', 'api', 'livewire', 'storage', 'media', 'build', 'themes', 'filament', 'up',
        'search', 'sitemap.xml', 'robots.txt', 'feed',
        'faculties', 'departments', 'programs', 'offices', 'labs', 'office', 'lab',
        'news', 'notices', 'events', 'gallery', 'downloads', 'faq', 'contact',
        'admission', 'tuition-fees', 'waiver-calculator', 'scholarships', 'academic-calendar',
        'faculty-members', 'administration', 'leadership', 'committees', 'alumni', 'transport',
        'facilities', 'rankings', 'international', 'csr', 'partners', 'courses', 'curriculum',
        'services', 'support-contacts',
    ],
    // Reserved first segments a main site page may take: the module's landing is a page (DB-02 v0.2 §8.1)
    'page_landing_paths' => ['contact', 'admission'],
    'reserved_microsite_paths' => [
        'news', 'notices', 'events', 'gallery', 'downloads', 'faq', 'contact', 'search',
        'faculty-members', 'people', 'programs', 'courses', 'curriculum', 'labs', 'lab',
        'committees', 'achievements', 'publications', 'research', 'tuition-fees', 'admission',
    ],
];
