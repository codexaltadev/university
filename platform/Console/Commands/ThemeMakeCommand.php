<?php

namespace Platform\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Platform\Theme\ThemeManifest;
use Platform\Theme\Themes;
use Symfony\Component\Finder\SplFileInfo;

/**
 * php artisan uwms:theme:make {slug} [--from=] [--blank] [--name=] (Theme Guide §3).
 *
 *   --from=default  copy an installed theme (its views, lang, resources, public files) under a new slug;
 *                   built assets, node_modules and the hot file are left behind — build them again
 *   --blank         (the default) the smallest theme the checker accepts: manifest, both layouts, the five
 *                   page templates, one header and footer, 404 / 503, lang, Tailwind 4 source and Vite config
 *
 * Either way the new theme needs `npx vite build --config themes/{slug}/vite.config.js` (T-6) and
 * `php artisan uwms:theme:sync` before it shows in Admin › Themes.
 */
class ThemeMakeCommand extends Command
{
    protected $signature = 'uwms:theme:make {slug : lowercase letters, digits and dashes, e.g. campus}
                            {--from= : copy this installed theme instead of starting blank}
                            {--blank : start from the minimal skeleton (default)}
                            {--name= : display name (default: the slug as a title)}';

    protected $description = 'Create a new website theme in themes/';

    /** Left behind when copying a theme: build output and local tooling. */
    protected const SKIP = '#^(node_modules|public/build|public/hot)(/|$)#';

    public function handle(Themes $themes): int
    {
        $slug = (string) $this->argument('slug');
        $name = (string) ($this->option('name') ?: Str::headline($slug));
        $from = $this->option('from');

        if (! preg_match('/^[a-z][a-z0-9-]{1,49}$/', $slug)) {
            $this->components->error('The slug must be lowercase letters, digits and dashes (2–50, starting with a letter).');

            return self::FAILURE;
        }

        $target = base_path("themes/{$slug}");

        if (File::exists($target) || isset($themes->discover()[$slug])) {
            $this->components->error("A theme \"{$slug}\" already exists.");

            return self::FAILURE;
        }

        if ($from && $this->option('blank')) {
            $this->components->error('Use either --from or --blank.');

            return self::FAILURE;
        }

        if ($from) {
            $source = $themes->discover()[$from] ?? null;

            if (! $source) {
                $this->components->error("Theme \"{$from}\" is not installed.");

                return self::FAILURE;
            }

            $this->copy($source, $target, $slug, $name, $from);
        } else {
            foreach ($this->blankFiles($slug, $name) as $file => $content) {
                File::ensureDirectoryExists(dirname("{$target}/{$file}"));
                File::put("{$target}/{$file}", $content);
            }
        }

        $this->components->info("Theme {$name} created in themes/{$slug}.");
        $this->line('  Next:');
        $this->line("    npx vite build --config themes/{$slug}/vite.config.js   # compiled assets (T-6)");
        $this->line('    php artisan uwms:theme:sync                            # list it in Admin › Themes');
        $this->line("    php artisan uwms:theme:check {$slug}");

        return self::SUCCESS;
    }

    protected function copy(string $source, string $target, string $slug, string $name, string $from): void
    {
        /** @var SplFileInfo $file */
        foreach (File::allFiles($source, true) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());

            if (preg_match(self::SKIP, $relative)) {
                continue;
            }

            File::ensureDirectoryExists(dirname("{$target}/{$relative}"));
            File::copy($file->getPathname(), "{$target}/{$relative}");
        }

        $manifest = ThemeManifest::fromDirectory($target)->data;
        $manifest = ['slug' => $slug, 'name' => $name, 'version' => '1.0.0'] + $manifest;
        $manifest['description'] = "Based on {$manifest['description']}";
        File::put("{$target}/theme.json", json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
        File::put("{$target}/CHANGELOG.md", "# Changelog — {$name}\n\n## 1.0.0\n\n- Created from the \"{$from}\" theme.\n");
    }

    /** @return array<string, string> path => content */
    protected function blankFiles(string $slug, string $name): array
    {
        $manifest = [
            'slug' => $slug,
            'name' => $name,
            'version' => '1.0.0',
            'requires_core' => '^'.Str::before((string) config('platform.version'), '.').'.0',
            'author' => ['name' => '', 'email' => ''],
            'license' => 'proprietary',
            'description' => "{$name} website theme.",
            'screenshot' => 'screenshot.png',
            'layouts' => (object) [],
            'menu_locations' => [
                'primary' => 'Primary (header)',
                'topbar' => 'Top bar',
                'footer-1' => 'Footer column 1',
                'footer-2' => 'Footer column 2',
                'footer-3' => 'Footer column 3',
                'sidebar' => 'Sidebar',
            ],
            'header_variants' => ['default'],
            'footer_variants' => ['default'],
            'page_templates' => ThemeManifest::PAGE_TEMPLATES,
            'microsite_templates' => ['standard'],
            'tokens' => (object) [],
            'options' => [],
            'sections' => (object) [],
        ];

        $aside = '            <aside><x-platform::menu location="sidebar" :depth="2" /></aside>';
        $template = fn (string $class, ?string $sidebar = null): string => implode("\n", array_filter([
            '{{-- Page template (FR-PAGE-04). Contract: $page (title), $body (rendered blocks, sanitised by the kernel) --}}',
            '<x-theme::layout :title="$page->title">',
            "    <div class=\"container page {$class}\">",
            '        <h1 class="page__title">{{ $page->title }}</h1>',
            $sidebar ? '        <div class="page-grid">' : null,
            $sidebar === 'left' ? $aside : null,
            ($sidebar ? '    ' : '').'        <div class="prose">{!! $body !!}</div>',
            $sidebar === 'right' ? $aside : null,
            $sidebar ? '        </div>' : null,
            '    </div>',
            '</x-theme::layout>',
        ]))."\n";

        $layout = fn (bool $microsite): string => <<<'BLADE'
            <!DOCTYPE html>
            <html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <x-platform::head :title="$title ?? null" :description="$description ?? null" />
                @themeVite(['resources/css/app.css', 'resources/js/app.js'])
                @livewireStyles
            </head>
            <body class="site">
                <a href="#main" class="skip-link">{{ __('theme::theme.skip_to_content') }}</a>

                <x-platform::slot name="topbar" />
                <x-platform::slot name="announcement" />
                <x-platform::slot name="header" />
            BLADE."\n".($microsite ? "    <x-platform::slot name=\"microsite-bar\" />\n" : '').<<<'BLADE'
                <x-platform::slot name="ticker" />
                <x-platform::slot name="breadcrumb" />

                <main id="main">
                    {{ $slot }}
                </main>

                <x-platform::slot name="footer" />
                <x-platform::slot name="popup" />

                @livewireScripts
                {!! settings('code.footer_code') !!}
            </body>
            </html>

            BLADE;

        $lang = fn (string $skip, string $home, string $notFound, string $down): string => "<?php\n\nreturn [\n    'skip_to_content' => '{$skip}',\n    'home' => '{$home}',\n    'not_found' => '{$notFound}',\n    'maintenance' => '{$down}',\n];\n";

        return [
            'theme.json' => json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n",
            'README.md' => "# {$name}\n\nWebsite theme (Theme Guide: docs/07-DevGuide/02-Theme-Development-Guide.md).\n\n```bash\nnpx vite build --config themes/{$slug}/vite.config.js\nphp artisan uwms:theme:sync\nphp artisan uwms:theme:check {$slug}\n```\n",
            'CHANGELOG.md' => "# Changelog — {$name}\n\n## 1.0.0\n\n- Created with `uwms:theme:make {$slug} --blank`.\n",
            'views/layouts/app.blade.php' => $layout(false),
            'views/layouts/microsite.blade.php' => $layout(true),
            'views/templates/default.blade.php' => $template('page--default'),
            'views/templates/full-width.blade.php' => $template('page--full-width'),
            'views/templates/with-sidebar.blade.php' => $template('page--with-sidebar', 'left'),
            'views/templates/right-sidebar.blade.php' => $template('page--right-sidebar', 'right'),
            'views/templates/landing.blade.php' => $template('page--landing'),
            'views/microsite-templates/standard.blade.php' => "{{-- Microsite homepage frame; sections come from the homepage builder --}}\n<x-theme::layout>\n    <x-platform::homepage :context=\"\$context\" />\n</x-theme::layout>\n",
            'views/home.blade.php' => "{{-- Main homepage: the homepage builder's sections (C-10) --}}\n<x-theme::layout>\n    <x-platform::homepage :context=\"\$context\" />\n</x-theme::layout>\n",
            'views/partials/headers/default.blade.php' => "{{-- Header (FR-HF-01): logo and the primary menu --}}\n<header class=\"site-header\">\n    <div class=\"container\">\n        <a href=\"{{ url('/') }}\"><x-platform::logo variant=\"light\" /></a>\n        <x-platform::menu location=\"primary\" :depth=\"2\" />\n        <x-platform::languages />\n    </div>\n</header>\n",
            'views/partials/footers/default.blade.php' => "{{-- Footer (FR-HF-03): the admin's columns, else the footer menus; copyright --}}\n<footer class=\"site-footer\">\n    <div class=\"container\">\n        @forelse (\$config['columns'] ?? [] as \$column)\n            <div>\n                @foreach (\$column['blocks'] ?? [] as \$block)\n                    <x-platform::footer-block :block=\"\$block\" />\n                @endforeach\n            </div>\n        @empty\n            @foreach (['footer-1', 'footer-2', 'footer-3'] as \$location)\n                <x-platform::menu :location=\"\$location\" :depth=\"1\" />\n            @endforeach\n        @endforelse\n        <p>{{ \$region['copyright'] ?? '© '.now()->year.' '.settings('general.site_name') }}</p>\n    </div>\n</footer>\n",
            'views/errors/404.blade.php' => "<x-theme::layout :title=\"__('theme::theme.not_found')\">\n    <div class=\"container page\">\n        <h1 class=\"page__title\">{{ __('theme::theme.not_found') }}</h1>\n        <p><a class=\"btn btn--primary\" href=\"{{ url('/') }}\">{{ __('theme::theme.home') }}</a></p>\n    </div>\n</x-theme::layout>\n",
            'views/errors/503.blade.php' => "<x-theme::layout :title=\"__('theme::theme.maintenance')\">\n    <div class=\"container page\">\n        <h1 class=\"page__title\">{{ __('theme::theme.maintenance') }}</h1>\n    </div>\n</x-theme::layout>\n",
            'lang/en/theme.php' => $lang('Skip to content', 'Home', 'Page not found', 'We will be back soon'),
            'lang/bn/theme.php' => $lang('মূল লেখায় যান', 'হোম', 'পাতাটি পাওয়া যায়নি', 'শিগগিরই ফিরছি'),
            'resources/css/app.css' => <<<'CSS'
                /* Tailwind CSS 4.3.3 bound to the kernel's design tokens (Theme Guide §6); no hex colours here (T-2) */
                @import "tailwindcss";

                @source "../../views";
                @source "../../../../Modules/*/resources/views";
                @source "../../../../platform/resources/views";

                @theme inline reference {
                    --color-primary: var(--color-primary);
                    --color-primary-contrast: var(--color-primary-contrast);
                    --color-secondary: var(--color-secondary);
                    --color-accent: var(--color-accent);
                    --color-bg: var(--color-bg);
                    --color-surface: var(--color-surface);
                    --color-text: var(--color-text);
                    --color-muted: var(--color-text-muted);
                    --color-border: var(--color-border);
                    --font-heading: var(--font-heading);
                    --font-body: var(--font-body);
                }

                @layer base {
                    [x-cloak] { display: none !important; }
                    body.site { margin: 0; background: var(--color-bg); color: var(--color-text); font-family: var(--font-body); line-height: var(--line-height-body); }
                }

                /* Module UI class contract (UI/UX Part B §9.1): every theme styles these */
                @layer components {
                    .container { max-width: var(--container-max); margin-inline: auto; padding-inline: var(--space-4); }
                    .skip-link { position: absolute; left: -9999px; } .skip-link:focus { left: var(--space-2); }
                    .btn { display: inline-flex; align-items: center; min-height: 44px; padding: var(--space-2) var(--space-5); border: 1px solid var(--color-primary); border-radius: var(--radius-md); color: var(--color-primary); }
                    .btn--primary { background: var(--color-primary); color: var(--color-primary-contrast); }
                    .btn--secondary { background: var(--color-secondary); color: var(--color-secondary-contrast); }
                    .btn--outline { background: transparent; }
                    .btn--sm { min-height: 36px; padding: var(--space-1) var(--space-3); }
                    .badge { display: inline-flex; padding: 2px var(--space-2); border-radius: 9999px; background: var(--color-surface); }
                    .badge--success { color: var(--color-success); } .badge--muted { color: var(--color-text-muted); }
                    .badge--warning { color: var(--color-warning); } .badge--danger { color: var(--color-danger); }
                    .card { border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: var(--space-6); }
                    .prose { max-width: 75ch; } .lead { font-size: var(--text-lg); }
                    .table { width: 100%; border-collapse: collapse; }
                    .empty-state { padding: var(--space-10); text-align: center; color: var(--color-text-muted); }
                    .form-error { color: var(--color-danger); }
                    .accordion__item { border: 1px solid var(--color-border); } .accordion__title { display: flex; width: 100%; } .accordion__body { padding: var(--space-4); }
                    .page { padding-block: var(--space-10); } .page__title { font-size: var(--text-3xl); }
                    .page-grid { display: grid; gap: var(--space-8); }
                    .home-section { padding-block: var(--space-12); } .home-section__title { font-size: var(--text-2xl); } .home-section__sub { color: var(--color-text-muted); }
                }

                @media print { .no-print { display: none !important; } }

                CSS,
            'resources/js/app.js' => "// Alpine.js comes with Livewire (Theme Guide §12): register components on alpine:init, never load a second copy.\ndocument.documentElement.classList.add('js');\n",
            'vite.config.js' => <<<JS
                // Theme asset build (Theme Guide §12), from the repository root:
                //   npx vite build --config themes/{$slug}/vite.config.js
                import { defineConfig } from 'vite';
                import laravel from 'laravel-vite-plugin';
                import tailwindcss from '@tailwindcss/vite';
                import { fileURLToPath } from 'node:url';

                const root = fileURLToPath(new URL('.', import.meta.url));

                export default defineConfig({
                    root,
                    plugins: [
                        tailwindcss(),
                        laravel({
                            input: ['resources/css/app.css', 'resources/js/app.js'],
                            publicDirectory: 'public',
                            buildDirectory: 'build',
                            hotFile: `\${root}public/hot`,
                            refresh: ['views/**', '../../Modules/*/resources/views/**', '../../platform/resources/views/**'],
                        }),
                    ],
                    build: { outDir: `\${root}public/build`, emptyOutDir: true },
                    experimental: { renderBuiltUrl: () => ({ relative: true }) },
                });

                JS,
        ];
    }
}
