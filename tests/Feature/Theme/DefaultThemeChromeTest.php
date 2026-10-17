<?php

/*
| Phase 4.2 — Default theme chrome (UI/UX Part B §4, §6): top bar with the language
| switcher, header with search panel and mobile drawer, header button (theme options),
| microsite identity beside the logo and in the microsite bar.
*/

use Illuminate\Support\Facades\Blade;
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

it('draws the header with a search panel and a mobile drawer', function () {
    $this->get('/')->assertOk()
        ->assertSee('x-data="siteHeader"', false)
        ->assertSee('aria-controls="site-search"', false)
        ->assertSee('id="instant-search-q"', false)
        ->assertSee('id="site-drawer"', false)
        ->assertSee('aria-modal="true"', false)
        ->assertSee(__('theme::theme.open_menu'));
});

it('links this page in every website language from the top bar (BR-LANG)', function () {
    $links = app(Seo::class)->languageLinks();

    expect(array_column($links, 'code'))->toBe(['en', 'bn'])
        ->and(array_column($links, 'current'))->toBe([true, false]);

    $this->get('/')->assertOk()
        ->assertSee('class="language-switcher topbar__languages"', false)   // kernel class + the theme's
        ->assertSee('hreflang="bn"', false)
        ->assertSee('বাংলা');
});

it('shows the header button only when the theme option has a link', function () {
    $themes = app(Themes::class);

    $this->get('/')->assertDontSee('site-header__cta', false);

    $themes->saveOptions($themes->active(), ['header_cta_url' => 'https://admission.example.edu']);
    $this->get('/')->assertSee('https://admission.example.edu', false)->assertSee(__('theme::theme.apply_now'));

    $themes->saveOptions($themes->active(), ['header_cta_url' => 'https://admission.example.edu', 'header_cta_label' => 'Apply for Spring']);
    $this->get('/')->assertSee('Apply for Spring');
});

it('names the microsite and its parent beside the logo and in the microsite bar (UI/UX Part B §4.2)', function () {
    $identity = SiteContext::for($this->cse)->identity();

    expect($identity['name'])->toBe('CSE')
        ->and($identity['url'])->toBe('/engineering/cse')
        ->and($identity['parent'])->toBe(['name' => 'Engineering', 'url' => '/test-faculty/engineering'])
        ->and(SiteContext::site()->identity())->toBeNull();

    expect(renderSlot('header', SiteContext::for($this->cse)))
        ->toContain('site-brand__name')->toContain('/engineering/cse')->toContain('/test-faculty/engineering');

    expect(renderSlot('microsite-bar', SiteContext::for($this->cse)))->toContain('microsite-bar__name')
        ->and(renderSlot('microsite-bar'))->not->toContain('microsite-bar')
        ->and(renderSlot('topbar', SiteContext::for($this->cse)))->toContain('topbar__back');
});

it('opens every menu level as an accordion in the drawer', function () {
    $html = view('theme::partials.menus.offcanvas', ['items' => MenuItems::from([
        ['label' => 'About', 'url' => '/about', 'children' => [['label' => 'Mission', 'url' => '/about/mission', 'children' => []]]],
        ['label' => 'Contact', 'url' => '/contact', 'children' => []],
    ], 3)])->render();

    expect($html)->toContain('x-collapse')->toContain('/about/mission')->toContain('drawer-nav__link--overview')->toContain('/contact');
});
