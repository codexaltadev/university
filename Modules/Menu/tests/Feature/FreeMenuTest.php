<?php

/*
| DB-02 v0.2 — free menus (no location) and a page's own sidebar menu (§5.1, §8.1), a module's
| landing page at a reserved address and module page addresses kept from child pages.
*/

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Menu\Filament\Pages\ManageMenus;
use Modules\Menu\Models\Menu;
use Modules\Menu\Support\MenuEditor;
use Modules\Page\Filament\Resources\Pages\Pages\CreatePage;
use Modules\Page\Models\Page;
use Platform\Context\UrlRules;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->editor = app(MenuEditor::class);
});

it('keeps any number of free menus beside the one menu per location', function () {
    $admission = $this->editor->createFree('site:0', 'Admission');
    $medical = $this->editor->createFree('site:0', 'Medical');
    $this->editor->create('site:0', 'sidebar');

    expect(MenuEditor::freeMenus('site:0'))->toBe([$admission->id => 'Admission', $medical->id => 'Medical'])
        ->and($this->editor->own('site:0', MenuEditor::FREE.$admission->id)?->id)->toBe($admission->id)
        ->and($this->editor->own('site:0', 'sidebar')?->location)->toBe('sidebar');
});

it('makes, names and fills a free menu on the Menus screen', function () {
    $this->actingAs(superAdmin());

    $screen = Livewire::withQueryParams(['owner' => 'site:0', 'location' => 'primary'])->test(ManageMenus::class)
        ->callAction('newFreeMenu', ['name' => 'Admission']);
    $menu = Menu::query()->whereNull('location')->sole();
    expect($screen->get('location'))->toBe(MenuEditor::FREE.$menu->id);

    $screen->fillForm(['name' => 'Admission sections', 'items' => [['type' => 'url', 'url' => '/admission/schedule', 'title' => ['en' => 'Admission Schedule'], 'is_active' => true]]])
        ->call('save');
    expect($menu->fresh()->name)->toBe('Admission sections')->and($menu->items()->count())->toBe(1);

    $screen->call('deleteOwn');
    expect(Menu::query()->whereNull('location')->count())->toBe(0)->and($screen->get('location'))->toBe('primary');
});

it('shows a page\'s own sidebar menu on it and the pages inside it, the owner\'s elsewhere', function () {
    $own = $this->editor->createFree('site:0', 'Admission');
    $this->editor->save($own, [['type' => 'url', 'url' => '/admission/schedule', 'title' => ['en' => 'Admission Schedule']]]);
    $site = $this->editor->create('site:0', 'sidebar');
    $this->editor->save($site, [['type' => 'url', 'url' => '/about', 'title' => ['en' => 'General Sidebar']]]);

    $admission = Page::factory()->create(['title' => ['en' => 'Admission'], 'template' => 'with-sidebar', 'sidebar_menu_id' => $own->id]);
    $admission->changeSlug('en', 'admission');
    Page::factory()->create(['title' => ['en' => 'Schedule'], 'parent_id' => $admission->id, 'template' => 'with-sidebar']);
    Page::factory()->create(['title' => ['en' => 'Campus Life'], 'template' => 'with-sidebar']);

    $this->get('/admission')->assertOk()->assertSee('Admission Schedule')->assertDontSee('General Sidebar');   // a module's landing path is fine for a page
    $this->get('/admission/schedule')->assertOk()->assertSee('Admission Schedule');                         // the parent's menu
    $this->get('/campus-life')->assertOk()->assertSee('General Sidebar')->assertDontSee('Admission Schedule');
});

it('offers the owner\'s free menus on the page form', function () {
    $this->actingAs(superAdmin());
    $menu = $this->editor->createFree('site:0', 'Admission');

    Livewire::test(CreatePage::class)
        ->fillForm(['owner_key' => 'site:0', 'template' => 'with-sidebar', 'title' => ['en' => 'Admission'], 'web_address' => 'admission', 'sidebar_menu_id' => $menu->id])
        ->call('create')->assertHasNoFormErrors();

    expect(Page::query()->sole()->sidebar_menu_id)->toBe($menu->id);
});

it('keeps module page addresses from pages, and reserved ones from all but landing pages', function () {
    expect(UrlRules::modulePageClash('/scholarships/merit'))->not->toBeNull()
        ->and(UrlRules::modulePageClash('/scholarships'))->not->toBeNull()
        ->and(UrlRules::modulePageClash('/about/merit'))->toBeNull()
        ->and(UrlRules::pageClash('site:0', 'admission'))->toBeNull()
        ->and(UrlRules::pageClash('site:0', 'notices'))->not->toBeNull();
});
