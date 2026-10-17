<?php

/*
| Phase 4.1 — Default theme build foundation (Theme Guide §6, §12, §13; UI/UX Part B §9;
| NFR-PERF-02): Tailwind 4.3.3 build bound to the design tokens, the module UI
| class contract, token lint (T-2) and the asset size budget.
*/

use Illuminate\Support\Facades\File;
use Platform\Theme\Themes;

function defaultThemePath(string $path = ''): string
{
    return base_path('themes/default'.($path !== '' ? '/'.$path : ''));
}

/** @return array<string, array{file: string}> */
function defaultThemeManifest(): array
{
    return json_decode(File::get(defaultThemePath('public/build/manifest.json')), true);
}

function defaultThemeCss(): string
{
    return File::get(defaultThemePath('public/build/'.defaultThemeManifest()['resources/css/app.css']['file']));
}

it('ships a prebuilt Vite manifest for both entries', function () {
    $manifest = defaultThemeManifest();

    foreach (['resources/css/app.css', 'resources/js/app.js'] as $entry) {
        expect($manifest)->toHaveKey($entry)
            ->and(File::exists(defaultThemePath('public/build/'.$manifest[$entry]['file'])))->toBeTrue();
    }
});

it('pins Tailwind CSS 4.3.3 for the theme build', function () {
    $package = json_decode(File::get(base_path('package.json')), true);

    expect($package['devDependencies']['tailwindcss'])->toBe('4.3.3')
        ->and($package['devDependencies']['@tailwindcss/vite'])->toBe('4.3.3')
        ->and($package['scripts'])->toHaveKey('build:theme');
});

it('styles every class of the module UI contract', function (string $class) {
    expect(defaultThemeCss())->toMatch('/\.'.preg_quote($class, '/').'(?![\w-])/');
})->with([
    'container', 'btn', 'btn--primary', 'btn--secondary', 'btn--outline', 'btn--sm',
    'badge', 'badge--success', 'badge--muted', 'badge--warning', 'badge--danger',
    'card', 'prose', 'lead', 'table', 'empty-state', 'form-error', 'no-print', 'sr-only',
    'accordion__item', 'accordion__title', 'accordion__body',
    'page', 'page__title', 'page-grid', 'home-section', 'home-section__title', 'home-section__sub',
]);

it('takes colours only from the design tokens (T-2)', function () {
    $css = defaultThemeCss();

    // No palette and no theme-written token values: the kernel's :root decides
    expect($css)->not->toMatch('/--color-(red|blue|green|gray|slate|zinc|neutral)-\d+/')
        ->and($css)->not->toMatch('/--color-primary:\s*var\(--color-primary\)/')
        ->and($css)->toContain('var(--color-primary)');

    $sources = collect(File::allFiles(defaultThemePath('views')))
        ->merge(File::allFiles(defaultThemePath('resources')))
        ->filter(fn ($file): bool => in_array($file->getExtension(), ['php', 'css', 'js'], true));

    foreach ($sources as $file) {
        $code = File::get($file->getPathname());

        expect(preg_match('/(?<![&\w])#[0-9a-fA-F]{3,8}\b(?![\w-])/', $code))->toBe(0, "hex colour in {$file->getRelativePathname()}")
            ->and(preg_match('/\b(bg|text|border|ring|from|to|fill|stroke)-\[#/', $code))->toBe(0, "arbitrary colour in {$file->getRelativePathname()}");
    }
});

it('stays inside the asset budget (NFR-PERF-02: CSS ≤ 80 KB, JS ≤ 120 KB gzip)', function () {
    $manifest = defaultThemeManifest();
    $gzip = fn (string $entry): int => strlen(gzencode(File::get(defaultThemePath('public/build/'.$manifest[$entry]['file'])), 9));

    expect($gzip('resources/css/app.css'))->toBeLessThanOrEqual(80 * 1024)
        ->and($gzip('resources/js/app.js'))->toBeLessThanOrEqual(120 * 1024);
});

it('links the built assets on the website', function () {
    $themes = app(Themes::class);
    $themes->sync();

    $html = (string) $themes->vite(['resources/css/app.css', 'resources/js/app.js']);
    $manifest = defaultThemeManifest();

    expect($html)->toContain('themes/default/build/'.$manifest['resources/css/app.css']['file'])
        ->and($html)->toContain('themes/default/build/'.$manifest['resources/js/app.js']['file']);
});
