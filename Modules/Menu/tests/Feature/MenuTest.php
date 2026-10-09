<?php

/*
| Phase 2.7b — Menus (C-08): fallback per location and mode, copy, tree save,
| visibility, permissions (FR-MENU-01..12, BRL-MENU-01..03, AC-MENU-01..03).
*/

use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Menu\Filament\Pages\ManageMenus;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;
use Modules\Menu\Support\MenuEditor;
use Modules\Menu\Support\MenuLinks;
use Modules\Menu\Support\MenuTree;
use Modules\Page\Models\Page;
use Platform\Context\SiteContext;
use Platform\Media\MediaLibrary;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Permission::findOrCreate('Manage:Menu', 'web');
    Permission::findOrCreate('ManageMain:Menu', 'web');

    $this->engineering = faculty();
    $this->cse = department($this->engineering, 'CSE');
    $this->k = fn ($m) => $m->getMorphClass().':'.$m->id;
    $this->editor = app(MenuEditor::class);
    $this->tree = app(MenuTree::class);
});

function menuWith(string $owner, string $location, array $titles): Menu
{
    $menu = app(MenuEditor::class)->create($owner, $location);
    app(MenuEditor::class)->save($menu, array_map(fn (string $t) => ['type' => 'url', 'url' => '/'.strtolower($t), 'title' => ['en' => $t]], $titles));

    return $menu;
}

function titlesOf(array $tree): array
{
    return array_column($tree, 'title');
}

it('falls back own → parent → main site, per location (AC-MENU-01/02, BRL-MENU-01)', function () {
    menuWith('site:0', 'primary', ['Home', 'Admission']);
    menuWith('site:0', 'footer-1', ['Contact']);
    menuWith(($this->k)($this->engineering), 'primary', ['Engineering Home']);
    $cse = SiteContext::for($this->cse);

    expect(titlesOf($this->tree->forLocation('primary', $cse)))->toBe(['Engineering Home'])      // parent's
        ->and(titlesOf($this->tree->forLocation('footer-1', $cse)))->toBe(['Contact']);         // main site's

    $own = menuWith(($this->k)($this->cse), 'primary', ['CSE Home']);
    expect(titlesOf($this->tree->forLocation('primary', $cse)))->toBe(['CSE Home']);

    $this->editor->delete($own);
    expect(titlesOf($this->tree->forLocation('primary', $cse)))->toBe(['Engineering Home']);

    $this->editor->setMode(($this->k)($this->cse), 'main');                                     // FR-MENU-08
    expect(titlesOf($this->tree->forLocation('primary', $cse)))->toBe(['Home', 'Admission']);
});

it('keeps one menu per owner and location (BRL-MENU-03)', function () {
    menuWith('site:0', 'primary', ['Home']);

    expect(fn () => $this->editor->create('site:0', 'primary'))->toThrow(QueryException::class);
});

it('hides items whose page is off, unpublished or deleted (FR-MENU-10, AC-MENU-03)', function () {
    $about = Page::factory()->create(['title' => ['en' => 'About Us', 'bn' => 'আমাদের সম্পর্কে']]);
    $menu = $this->editor->create('site:0', 'primary');
    $this->editor->save($menu, [
        ['type' => 'record', 'record_type' => 'page.page', 'record_id' => $about->id, 'title' => []],
        ['type' => 'heading', 'title' => ['en' => 'Study'], 'children' => [
            ['type' => 'url', 'url' => 'https://example.edu', 'title' => ['en' => 'Portal'], 'open_in_new_tab' => true],
        ]],
    ]);

    $tree = $this->tree->of($menu->refresh(), 'en');
    expect($tree[0])->toMatchArray(['title' => 'About Us', 'url' => '/about-us'])                 // the page's own title
        ->and($tree[1]['url'])->toBeNull()                                                       // heading
        ->and($tree[1]['children'][0])->toMatchArray(['title' => 'Portal', 'new_tab' => true])
        ->and($this->tree->of($menu, 'bn')[0]['title'])->toBe('আমাদের সম্পর্কে');

    $about->update(['is_active' => false]);
    expect(titlesOf($this->tree->of($menu, 'en')))->toBe(['Study'])
        ->and(app(MenuLinks::class)->problem(MenuItem::where('type', 'record')->first()))->toBe('hidden');

    $about->update(['is_active' => true]);
    $about->delete();
    expect(titlesOf($this->tree->of($menu, 'en')))->toBe(['Study']);
});

it('builds, copies and deletes menus on screen', function () {
    menuWith(($this->k)($this->engineering), 'primary', ['Engineering Home', 'Departments']);
    $this->actingAs(superAdmin());
    $cse = ($this->k)($this->cse);

    $page = Livewire::withQueryParams(['owner' => $cse, 'location' => 'primary'])->test(ManageMenus::class)
        ->assertSee('Engineering')                                                               // "uses Engineering's menu"
        ->call('createOwn', true);                                                               // copy and edit (FR-MENU-09)

    $own = $this->editor->own($cse, 'primary');
    expect($own->items()->count())->toBe(2);

    $page->set('data.items', [
        ['type' => 'url', 'url' => '/cse', 'title' => ['en' => 'CSE Home'], 'is_active' => true, 'children' => [
            ['type' => 'url', 'url' => '/cse/labs', 'title' => ['en' => 'Labs'], 'is_active' => true, 'children' => []],
        ]],
    ])->call('save');

    $tree = $this->tree->forLocation('primary', SiteContext::for($this->cse));
    expect(titlesOf($tree))->toBe(['CSE Home'])
        ->and(titlesOf($tree[0]['children']))->toBe(['Labs'])
        ->and(MenuItem::withTrashed()->where('menu_id', $own->id)->count())->toBe(2);            // old items gone

    $page->call('deleteOwn');
    expect(titlesOf($this->tree->forLocation('primary', SiteContext::for($this->cse))))->toBe(['Engineering Home', 'Departments']);
});

it('opens as dropdown, multi-level or mega menu; each top item may differ', function () {
    Storage::fake('public');
    $picture = app(MediaLibrary::class)->store(UploadedFile::fake()->image('campus.jpg'));
    $menu = $this->editor->create('site:0', 'primary');
    $link = fn (string $t, array $children = []) => ['type' => 'url', 'url' => '/'.strtolower($t), 'title' => ['en' => $t], 'children' => $children];

    $this->editor->save($menu, [
        [...$link('Academics', [$link('Faculties', [$link('Engineering')]), $link('Programs', [$link('BSc in CSE')])]),
            'options' => ['columns' => 3, 'image' => $picture->id, 'description' => ['en' => 'Study with us'], 'cta_label' => ['en' => 'All programs'], 'cta_url' => '/programs']],
        [...$link('About', [$link('History')]), 'options' => ['display' => 'dropdown']],
        $link('Deep', [$link('L2', [$link('L3', [$link('L4', [$link('L5')])])])]),
    ], style: 'mega');

    $menu->refresh();
    $tree = $this->tree->of($menu, 'en');
    expect($menu->style)->toBe('mega')
        ->and($tree[0])->toMatchArray(['mega' => true, 'columns' => 3, 'description' => 'Study with us', 'cta' => ['label' => 'All programs', 'url' => '/programs']])
        ->and($tree[0]['image'])->toBe($picture->url('medium'))
        ->and($tree[1]['mega'])->toBeFalse()                                                     // this item stays a dropdown
        ->and($tree[0]['children'][0]['children'][0]['title'])->toBe('Engineering')             // column → links
        ->and($tree[2]['children'][0]['children'][0]['children'][0]['title'])->toBe('L4')      // four levels …
        ->and($tree[2]['children'][0]['children'][0]['children'][0]['children'])->toBe([])     // … not five
        ->and($picture->refresh()->usage_count)->toBe(1);

    $this->editor->save($menu, $this->editor->tree($menu), style: 'multilevel');
    expect($this->tree->of($menu->refresh(), 'en')[0]['mega'])->toBeFalse()                      // follows the menu again
        ->and($this->tree->menuAt('primary', SiteContext::site(), 'en')['style'])->toBe('multilevel')
        ->and($this->editor->create(($this->k)($this->cse), 'primary', $menu)->style)->toBe('multilevel');   // copies keep the style
});

it('saves the menu style from the screen', function () {
    $this->actingAs(superAdmin());
    $this->editor->create('site:0', 'primary');

    Livewire::withQueryParams(['owner' => 'site:0', 'location' => 'primary'])->test(ManageMenus::class)
        ->assertSet('data.style', 'dropdown')
        ->set('data.style', 'mega')
        ->set('data.items', [['type' => 'url', 'url' => '/a', 'title' => ['en' => 'A'], 'is_active' => true, 'options' => ['display' => 'auto', 'columns' => 5], 'children' => []]])
        ->call('save');

    $menu = $this->editor->own('site:0', 'primary');
    expect($menu->style)->toBe('mega')
        ->and($menu->items()->first()->options)->toBe(['columns' => 5]);
});

it('lets entity users manage only their entities\' menus (BRL-MENU-02)', function () {
    $this->actingAs(makeUser())->get(ManageMenus::getUrl())->assertForbidden();

    $user = makeUser(['Manage:Menu'], [$this->cse]);
    $this->actingAs($user)->get(ManageMenus::getUrl())->assertOk();

    expect(MenuEditor::ownerKeys($user))->toBe([($this->k)($this->cse)]);                        // no main site

    Livewire::withQueryParams(['owner' => 'site:0'])->test(ManageMenus::class)
        ->assertSet('owner', ($this->k)($this->cse));                                            // falls back to their own
});
