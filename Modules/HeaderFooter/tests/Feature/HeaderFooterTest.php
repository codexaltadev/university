<?php

/*
| Phase 2.7c — Header & Footer (C-09): regions with fallback, footer columns,
| copyright with {year}, custom HTML for Super Admins, permissions (FR-HF-01..05, AC-HF-01).
*/

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\HeaderFooter\Filament\Pages\ManageHeaderFooter;
use Modules\HeaderFooter\Models\Region;
use Modules\HeaderFooter\Support\Regions;
use Platform\Context\FallbackResolver;
use Platform\Context\MicrositeOwners;
use Platform\Context\SiteContext;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Permission::findOrCreate('Manage:HeaderFooter', 'web');
    Permission::findOrCreate('ManageMain:HeaderFooter', 'web');

    $this->engineering = faculty();
    $this->cse = department($this->engineering, 'CSE');
    $this->k = fn ($m) => $m->getMorphClass().':'.$m->id;
    $this->regions = app(Regions::class);
});

function quickLinksColumn(): array
{
    return ['heading' => ['en' => 'Quick Links'], 'blocks' => [['type' => 'menu', 'menu_location' => 'quick-links']]];
}

it('shows the main site\'s footer column on every microsite page (AC-HF-01, FR-HF-05)', function () {
    $footer = $this->regions->create('site:0', 'footer');
    $footer->update(['config' => ['columns' => [quickLinksColumn()]], 'copyright' => ['en' => '© {year} Daffodil International University']]);
    $header = $this->regions->create(($this->k)($this->engineering), 'header');

    $cse = $this->regions->forContext('footer', SiteContext::for($this->cse), 'en');
    expect($cse['from'])->toBe('site:0')
        ->and($cse['config']['columns'][0]['blocks'][0])->toBe(['type' => 'menu', 'menu_location' => 'quick-links'])
        ->and($cse['copyright'])->toBe('© '.now()->year.' Daffodil International University')        // FR-HF-04
        ->and($this->regions->forContext('header', SiteContext::for($this->cse))['from'])->toBe(($this->k)($this->engineering));

    $this->regions->delete($header);
    expect($this->regions->forContext('header', SiteContext::for($this->cse)))->toBeNull();          // theme default
});

it('creates, copies, edits and deletes a microsite\'s own region on screen', function () {
    $main = $this->regions->create('site:0', 'footer');
    $main->update(['variant' => 'compact', 'config' => ['columns' => [quickLinksColumn()]]]);
    $this->actingAs(superAdmin());
    $cse = ($this->k)($this->cse);

    $page = Livewire::withQueryParams(['owner' => $cse, 'region' => 'footer'])->test(ManageHeaderFooter::class)
        ->assertSee('Main site')
        ->call('createOwn', true);                                                                    // copy and edit

    $own = $this->regions->own($cse, 'footer');
    expect($own->variant)->toBe('compact')->and($own->config['columns'][0]['blocks'][0]['menu_location'])->toBe('quick-links');

    $page->set('data.config.columns', [
        ['heading' => ['en' => 'About'], 'blocks' => [['type' => 'about_text', 'text' => ['en' => 'CSE since 2002']], ['type' => 'custom_html', 'html' => '<b>Hi</b>']]],
    ])->set('data.copyright', ['en' => '© {year} CSE'])->call('save');

    $own->refresh();
    expect($own->config['columns'][0]['blocks'][0]['text']['en'])->toBe('CSE since 2002')
        ->and($own->config['columns'][0]['blocks'][1]['html'])->toBe('<b>Hi</b>')
        ->and($own->copyrightText('en'))->toBe('© '.now()->year.' CSE');

    $page->call('deleteOwn');
    expect($this->regions->forContext('footer', SiteContext::for($this->cse))['from'])->toBe('site:0');
});

it('keeps custom HTML out of reach of everyone but Super Admins', function () {
    $main = $this->regions->create('site:0', 'footer');
    $main->update(['config' => ['columns' => [['heading' => [], 'blocks' => [['type' => 'custom_html', 'html' => '<p>Original</p>']]]]]]);
    $this->actingAs(makeUser(['ManageMain:HeaderFooter']));

    Livewire::withQueryParams(['owner' => 'site:0', 'region' => 'footer'])->test(ManageHeaderFooter::class)
        ->set('data.config.columns', [['heading' => [], 'blocks' => [['type' => 'custom_html', 'html' => '<script>alert(1)</script>']]]])
        ->call('save');

    expect($main->refresh()->config['columns'][0]['blocks'][0]['html'])->toBe('<p>Original</p>');
});

it('lets entity users edit only their entities\' regions', function () {
    $this->actingAs(makeUser())->get(ManageHeaderFooter::getUrl())->assertForbidden();

    $user = makeUser(['Manage:HeaderFooter'], [$this->cse]);
    $this->actingAs($user)->get(ManageHeaderFooter::getUrl())->assertOk();
    expect(MicrositeOwners::keys($user, 'HeaderFooter'))->toBe([($this->k)($this->cse)]);

    Livewire::withQueryParams(['owner' => 'site:0'])->test(ManageHeaderFooter::class)->assertSet('owner', ($this->k)($this->cse));
    expect(Region::count())->toBe(0);
});

it('draws the region\'s header variant on the website; one the theme lacks falls back (FR-HF-01, C-06)', function () {
    expect(array_keys(Regions::variants('header')))->toBe(['classic', 'centered', 'minimal']);    // from theme.json

    $this->get('/')->assertOk()->assertSee('site-header--classic', false);                     // nothing set: theme default

    $header = $this->regions->create('site:0', 'header');
    $header->update(['variant' => 'centered']);
    $this->get('/')->assertSee('site-header--centered', false);

    $header->update(['variant' => 'gone-in-this-theme']);
    app(FallbackResolver::class)->flush();                                                     // as the screen does after saving
    $this->get('/')->assertSee('site-header--classic', false);

    $footer = $this->regions->create('site:0', 'footer');
    $footer->update(['config' => ['columns' => [['heading' => ['en' => 'About us'], 'blocks' => [['type' => 'about_text', 'text' => ['en' => 'A green campus.']]]]]],
        'copyright' => ['en' => '© {year} Test University']]);
    $this->get('/')->assertSee('About us')->assertSee('A green campus.')->assertSee('© '.now()->year.' Test University');
});
