<?php

/*
| Phase 4.2 — Default theme chrome = daffodilvarsity.edu.bd (owner 2026-10-17; UI/UX Part B §4, §6):
| floating header with utility links, mega / dropdown menus, Services grid, search window and
| mobile menu; microsite name beside the logo; footer band, call-to-action and link columns.
*/

use Illuminate\Support\Facades\Blade;
use Modules\HeaderFooter\Support\Regions;
use Modules\Menu\Support\MenuEditor;
use Platform\Context\FallbackResolver;
use Platform\Context\SiteContext;
use Platform\Seo\Seo;
use Platform\Theme\MenuItems;
use Platform\Theme\Themes;

beforeEach(function () {
    app(Themes::class)->flush();
    $this->engineering = faculty('Engineering');
    $this->cse = department($this->engineering, 'CSE');
});

function renderSlot(string $name, ?SiteContext $context = null): string
{
    view()->share('context', $context ?? SiteContext::site());   // as the website controller does

    return Blade::render('<x-platform::slot :name="$name" />', ['name' => $name]);
}

/** @param list<array<string, mixed>> $tree */
function mainMenu(string $location, array $tree, string $style = 'dropdown'): void
{
    $editor = app(MenuEditor::class);
    $editor->save($editor->own('site:0', $location) ?? $editor->create('site:0', $location), $tree, $style);
    app(FallbackResolver::class)->flush();
}

function menuLink(string $title, ?string $url, array $children = [], array $extra = []): array
{
    return ['type' => $url ? 'url' : 'heading', 'url' => $url, 'title' => ['en' => $title], 'is_active' => true, 'children' => $children, ...$extra];
}

it('draws the floating header with the search window and the mobile menu', function () {
    $this->get('/')->assertOk()
        ->assertSee('x-data="siteHeader"', false)
        ->assertSee('aria-controls="site-search"', false)
        ->assertSee('id="instant-search-q"', false)
        ->assertSee(__('theme::theme.search_start'))
        ->assertSee('id="site-drawer"', false)
        ->assertSee('aria-modal="true"', false)
        ->assertSee(__('theme::theme.open_menu'));
});

it('shows utility links, a mega menu and the Services grid on the main site', function () {
    mainMenu('topbar', [menuLink('DIU News', 'https://news.example.edu', extra: ['open_in_new_tab' => true])]);
    mainMenu('quick-links-icon', [menuLink('Library', 'https://library.example.edu', extra: ['icon' => 'book-open'])]);
    mainMenu('primary', [menuLink('Admissions', null, [
        menuLink('Fees and Funding', null, [menuLink('Tuition Fees', '/tuition-fees')]),
    ], ['options' => ['display' => 'mega', 'columns' => 3]])], 'mega');

    $this->get('/')->assertOk()
        ->assertSee('header-utility', false)->assertSee('https://news.example.edu', false)
        ->assertSee('nav--mega', false)->assertSee('Fees and Funding')->assertSee('/tuition-fees', false)
        ->assertSee('repeat(3, minmax(0, 1fr))', false)
        ->assertSee('https://library.example.edu', false)->assertSee(__('theme::theme.services'));
});

it('links this page in every website language (BR-LANG)', function () {
    $links = app(Seo::class)->languageLinks();

    expect(array_column($links, 'code'))->toBe(['en', 'bn'])
        ->and(array_column($links, 'current'))->toBe([true, false]);

    $this->get('/')->assertOk()
        ->assertSee('language-switcher--pill', false)   // in the mobile menu
        ->assertSee('hreflang="bn"', false)
        ->assertSee('বাংলা');
});

it('takes the Apply button from the theme options; an empty link hides it', function () {
    $themes = app(Themes::class);

    $this->get('/')->assertSee('https://admission.daffodilvarsity.edu.bd/', false)->assertSee(__('theme::theme.apply_now'));

    $themes->saveOptions($themes->active(), ['header_cta_url' => 'https://apply.example.edu', 'header_cta_label' => 'Apply for Spring']);
    $this->get('/')->assertSee('https://apply.example.edu', false)->assertSee('Apply for Spring');

    $themes->saveOptions($themes->active(), ['header_cta_url' => '']);
    $this->get('/')->assertDontSee('site-header__cta', false);
});

it('names the microsite beside the logo, without utility links (UI/UX Part B §4.2)', function () {
    $identity = SiteContext::for($this->cse)->identity();

    expect($identity['name'])->toBe('CSE')
        ->and($identity['url'])->toBe('/engineering/cse')
        ->and($identity['parent'])->toBe(['name' => 'Engineering', 'url' => '/test-faculty/engineering'])
        ->and(SiteContext::site()->identity())->toBeNull();

    mainMenu('topbar', [menuLink('DIU News', 'https://news.example.edu')]);

    expect(renderSlot('header', SiteContext::for($this->cse)))
        ->toContain('microsite-name')->toContain('/engineering/cse')->toContain('font-mina')
        ->not->toContain('header-utility');

    expect(renderSlot('header'))->not->toContain('microsite-name')->toContain('header-utility')
        ->and(renderSlot('microsite-bar', SiteContext::for($this->cse)))->toBe('');   // DIU has no bar
});

it('draws the footer band, the call-to-action and the admin\'s link columns', function () {
    mainMenu('footer-1', [menuLink('Contact', '/article/contact')]);
    $regions = app(Regions::class);
    $footer = $regions->create('site:0', 'footer');
    $footer->update([
        'config' => ['columns' => [['heading' => ['en' => 'Get in Touch'], 'blocks' => [['type' => 'menu', 'menu_location' => 'footer-1']]]]],
        'copyright' => ['en' => 'Copyright © {year} Daffodil International University. All Rights Reserved.'],
    ]);
    app(FallbackResolver::class)->flush();

    $this->get('/')->assertOk()
        ->assertSee('Campus Life in 60 Seconds')->assertSee('Get a Virtual Tour')
        ->assertSee('Start Your Journey<br>', false)->assertSee('with Us')->assertSee('themes/default/images/footer-cta.jpg', false)
        ->assertSee('Get in Touch')->assertSee('/article/contact', false)
        ->assertSee('Copyright © '.now()->year.' Daffodil International University');

    $themes = app(Themes::class);
    $themes->saveOptions($themes->active(), ['footer_band_1_label' => '', 'footer_cta_title' => '']);
    $this->get('/')->assertDontSee('Campus Life in 60 Seconds')->assertDontSee('Start Your Journey');
});

it('opens every menu level as an accordion in the mobile menu', function () {
    $html = view('theme::partials.menus.offcanvas', ['items' => MenuItems::from([
        ['title' => 'About', 'url' => '/about', 'children' => [['title' => 'Mission', 'url' => '/about/mission', 'children' => []]]],
        ['title' => 'Contact', 'url' => '/contact', 'children' => []],
    ], 3)])->render();

    expect($html)->toContain('x-collapse')->toContain('/about/mission')->toContain('href="/about"')->toContain('/contact');
});
