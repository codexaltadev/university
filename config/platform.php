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

    /* Main Site pseudo-target in core_relations: ("site", 0) means "published on the main site" (ADR-15). */
    'main_site' => ['type' => 'site', 'id' => 0],
];
