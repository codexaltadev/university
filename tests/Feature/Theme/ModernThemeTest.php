<?php

/*
| University Modern theme (docs/00-MODERN-THEME.md, UI/UX Part B §11–12): a generic theme for any
| university — passes the checker, only design tokens (no palette, no hex), no university's name
| or links, the module UI contract styled, chrome and pages render, and switching back to the
| default theme leaves the DIU look as it was.
*/

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Platform\Context\SiteContext;
use Platform\Theme\ThemeChecker;
use Platform\Theme\Themes;
use Symfony\Component\Finder\SplFileInfo;

function modernThemePath(string $path = ''): string
{
    return base_path('themes/modern'.($path !== '' ? '/'.$path : ''));
}

function modernThemeCss(): string
{
    $manifest = json_decode(File::get(modernThemePath('public/build/manifest.json')), true);

    return File::get(modernThemePath('public/build/'.$manifest['resources/css/app.css']['file']));
}

/** @return Collection<int, SplFileInfo> */
function modernThemeSources()
{
    return collect(File::allFiles(modernThemePath('views')))
        ->merge(File::allFiles(modernThemePath('resources')))
        ->merge([new SplFileInfo(modernThemePath('theme.json'), '', 'theme.json')])
        ->merge(File::allFiles(modernThemePath('lang')))
        ->filter(fn (SplFileInfo $file): bool => in_array($file->getExtension(), ['php', 'css', 'js', 'json'], true));
}

function useModernTheme(): Themes
{
    $themes = app(Themes::class);
    $themes->sync();
    $themes->activate('modern');
    $themes->apply();   // theme:: views now point at modern (the website middleware does this per request)

    return $themes;
}

beforeEach(function () {
    Storage::fake('public');
    app(Themes::class)->flush();
});

afterEach(function () {
    $themes = app(Themes::class);
    $themes->sync();
    $themes->activate('default');
});

it('passes the theme checks and declares what the platform needs (FR-THEME-02, T-1, T-3, T-6)', function () {
    expect(app(ThemeChecker::class)->check(modernThemePath()))->toBe([]);

    $manifest = json_decode(File::get(modernThemePath('theme.json')), true);

    expect($manifest['slug'])->toBe('modern')
        ->and($manifest['name'])->toBe('University Modern')
        ->and($manifest['fonts'])->toBe(['Inter', 'Noto Sans Bengali'])
        ->and(array_keys($manifest['menu_locations']))->toContain('primary', 'topbar', 'footer-1', 'sidebar');
});

it('names no university and links nowhere in particular (UI/UX Part B §11)', function () {
    foreach (modernThemeSources() as $file) {
        expect(preg_match('/daffodil|\bDIU\b|\.edu\.bd/i', File::get($file->getPathname())))->toBe(0, "university-specific text in {$file->getRelativePathname()}");
    }
});

it('takes every colour from the design tokens: no hex, no Tailwind palette (T-2)', function () {
    foreach (modernThemeSources()->reject(fn (SplFileInfo $file): bool => $file->getFilename() === 'theme.json') as $file) {
        $code = File::get($file->getPathname());

        expect(preg_match('/(?<![&\w])#[0-9a-fA-F]{3,8}\b(?![\w-])/', $code))->toBe(0, "hex colour in {$file->getRelativePathname()}")
            ->and(preg_match('/\b(bg|text|border|ring|from|to|fill|stroke)-\[#/', $code))->toBe(0, "arbitrary colour in {$file->getRelativePathname()}")
            ->and(preg_match('/\b(bg|text|border|ring|from|to|via|fill|stroke|shadow)-(slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose)-\d{2,3}\b/', $code))
            ->toBe(0, "Tailwind palette colour in {$file->getRelativePathname()}");
    }

    $css = modernThemeCss();

    expect($css)->toContain('var(--color-primary)')
        ->not->toMatch('/--color-(primary|secondary|accent):\s*#/')
        ->not->toContain('--color-blue-500');   // the palette is switched off
});

it('styles every class of the module UI contract (UI/UX Part B §9.1)', function (string $class) {
    expect(modernThemeCss())->toMatch('/\.'.preg_quote($class, '/').'(?![\w-])/');
})->with([
    'container', 'btn', 'btn--primary', 'btn--secondary', 'btn--outline', 'btn--sm',
    'badge', 'badge--success', 'badge--muted', 'badge--warning', 'badge--danger',
    'card', 'prose', 'lead', 'table', 'empty-state', 'form-error', 'no-print', 'sr-only',
    'accordion__item', 'accordion__title', 'accordion__body',
    'page', 'page__title', 'page-grid', 'home-section', 'home-section__title', 'home-section__sub',
    'chip', 'chip-active', 'tabs__list', 'tabs__tab',
]);

it('stays inside the asset budget and bundles its fonts (NFR-PERF-02)', function () {
    $manifest = json_decode(File::get(modernThemePath('public/build/manifest.json')), true);
    $gzip = fn (string $entry): int => strlen(gzencode(File::get(modernThemePath('public/build/'.$manifest[$entry]['file'])), 9));

    expect($gzip('resources/css/app.css'))->toBeLessThanOrEqual(80 * 1024)
        ->and($gzip('resources/js/app.js'))->toBeLessThanOrEqual(120 * 1024)
        ->and(modernThemeCss())->toContain('font-family:Inter')->not->toContain('fonts.googleapis.com');
});

it('draws its header, footer and pages once activated, and the default theme again after (FR-THEME-03, BRL-THEME-01)', function () {
    useModernTheme();

    $this->get('/')->assertOk()
        ->assertSee('site-header--modern', false)
        ->assertSee('themes/modern/build/', false)
        ->assertSee('class="site-footer"', false)
        ->assertSee('id="site-drawer"', false)
        ->assertDontSee('themes/default/build/', false);

    $this->get('/this-page-does-not-exist')->assertNotFound()->assertSee('error-page__code', false);

    app(Themes::class)->activate('default');

    $this->get('/')->assertOk()
        ->assertSee('site-header--classic', false)
        ->assertSee('themes/default/build/', false)
        ->assertDontSee('site-header--modern', false);
});

it('shows the header button only when the admin fills it in (theme options)', function () {
    $themes = useModernTheme();

    $this->get('/')->assertDontSee('site-header__cta', false);

    $themes->saveOptions($themes->active(), ['header_cta_url' => '/admission', 'header_cta_label' => 'Apply now']);
    $this->get('/')->assertSee('site-header__cta', false)->assertSee('Apply now');
});

it('names the microsite in the header and in the microsite bar (UI/UX Part B §4.2)', function () {
    useModernTheme();
    $cse = department(faculty('Engineering'), 'CSE');
    view()->share('context', SiteContext::for($cse));

    expect(Blade::render('<x-platform::slot name="header" />'))->toContain('site-header__entity')->toContain('CSE')->toContain('Engineering')
        ->and(Blade::render('<x-platform::slot name="microsite-bar" />'))->toContain('microsite-bar__name')->toContain('/engineering/cse');

    view()->share('context', SiteContext::site());

    expect(Blade::render('<x-platform::slot name="microsite-bar" />'))->not->toContain('microsite-bar__name');
});

it('keeps one main landmark: only the layouts open <main>', function () {
    $nested = collect(File::allFiles(modernThemePath('views')))
        ->reject(fn (SplFileInfo $file): bool => str_starts_with(str_replace('\\', '/', $file->getRelativePathname()), 'layouts/'))
        ->filter(fn (SplFileInfo $file): bool => str_contains($file->getContents(), '<main'))
        ->map->getRelativePathname()->values()->all();

    expect($nested)->toBe([]);
});
