<?php

/*
| Phase 2.7e — Homepage Builder (C-10): order, module-off hiding, context content and
| source modes, schedule, theme default, copy, core sections, permissions
| (FR-HOME-01..12, AC-HOME-01/02).
*/

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Faq\Models\Faq;
use Modules\Homepage\Database\Seeders\HomepageDatabaseSeeder;
use Modules\Homepage\Filament\Pages\ManageHomepage;
use Modules\Homepage\Models\HomeSection;
use Modules\Homepage\Models\Layout;
use Modules\Homepage\Support\CachedItems;
use Modules\Homepage\Support\Homepages;
use Platform\Context\MicrositeOwners;
use Platform\Media\MediaLibrary;
use Platform\Modules\ModuleManager;
use Platform\Relations\RelationEngine;
use Platform\Statistics\StatItem;
use Platform\Statistics\StatSet;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Permission::findOrCreate('Manage:Homepage', 'web');
    Permission::findOrCreate('ManageMain:Homepage', 'web');

    $this->engineering = faculty();
    $this->cse = department($this->engineering, 'CSE');
    $this->eee = department($this->engineering, 'EEE');
    $this->k = fn ($m) => $m->getMorphClass().':'.$m->id;
    $this->homepages = app(Homepages::class);

    $this->layoutWith = function (string $owner, array $sections): Layout {
        $layout = $this->homepages->create($owner);
        foreach ($sections as $i => $section) {
            $layout->sections()->create([...$section, 'sort_order' => $i + 1]);
        }

        return $layout;
    };
});

function keysOf(?array $homepage): array
{
    return array_column($homepage ?? [], 'key');
}

it('shows sections in their order and hides those of inactive modules (AC-HOME-01/02, FR-HOME-09)', function () {
    ($this->layoutWith)('site:0', [
        ['section_type' => 'core.spacer'],
        ['section_type' => 'faq.highlights', 'heading' => ['en' => 'Questions']],
        ['section_type' => 'no-such-module.latest'],                                               // module not installed
    ]);

    expect(keysOf($this->homepages->forOwner('site:0')))->toBe(['core.spacer', 'faq.highlights']);

    app(ModuleManager::class)->syncBundled();
    app(ModuleManager::class)->deactivate('faq');
    expect(keysOf($this->homepages->forOwner('site:0')))->toBe(['core.spacer']);                  // the page still works
});

it('fills content sections from the homepage\'s own context, highlights first (FR-HOME-05/11)', function () {
    $engine = app(RelationEngine::class);
    $faq = function (string $q, $entity, bool $main = false) use ($engine): Faq {
        $f = Faq::factory()->create(['question' => $q]);   // created in order: the first is the oldest
        $engine->sync($f, [($this->k)($entity)], mainSite: $main, user: null);

        return $f;
    };
    $old = $faq('Old CSE question', $this->cse);
    $new = $faq('CSE question', $this->cse);
    $faq('EEE question', $this->eee);
    $faq('Main', $this->cse, main: true);
    $engine->sync($old, [($this->k)($this->cse)], highlights: [($this->k)($this->cse) => ['order' => 1]], user: null);

    ($this->layoutWith)(($this->k)($this->cse), [['section_type' => 'faq.highlights', 'settings' => ['count' => 5]]]);
    $items = $this->homepages->forOwner(($this->k)($this->cse))[0]['items'];
    expect($items->pluck('question')->all())->toBe(['Old CSE question', 'Main', 'CSE question']);  // highlighted first, then newest

    ($this->layoutWith)('site:0', [['section_type' => 'faq.highlights', 'settings' => ['source_mode' => 'latest']]]);
    expect($this->homepages->forOwner('site:0')[0]['items']->pluck('question')->all())->toBe(['Main']);   // main site = published there

    $layout = $this->homepages->own(($this->k)($this->cse));
    $layout->sections()->first()->update(['settings' => ['source_mode' => 'manual', 'manual_ids' => [$new->id]]]);
    Homepages::flush();
    expect($this->homepages->forOwner(($this->k)($this->cse))[0]['items']->pluck('question')->all())->toBe(['CSE question']);
});

it('reads section items back from a real cache store, where objects are not unserialized', function () {
    config(['cache.default' => 'database']);   // serializes like production (cache.serializable_classes = false)
    Cache::store('database')->flush();

    $faq = Faq::factory()->create(['question' => 'Cached question']);
    app(RelationEngine::class)->sync($faq, [], mainSite: true, user: null);
    ($this->layoutWith)('site:0', [['section_type' => 'faq.highlights', 'settings' => ['source_mode' => 'latest']]]);

    $first = $this->homepages->forOwner('site:0')[0]['items'];
    $second = $this->homepages->forOwner('site:0')[0]['items'];   // from the cache
    expect($second)->toBeInstanceOf(Collection::class)
        ->and($second->first())->toBeInstanceOf(Faq::class)
        ->and($second->pluck('question')->all())->toBe($first->pluck('question')->all());

    // Nested shapes (News by Category: [category, posts]) survive too, in order
    $frozen = CachedItems::freeze(collect([['category' => $faq, 'posts' => Faq::query()->get()], 'plain' => 3]));
    expect(unserialize(serialize($frozen), ['allowed_classes' => false]))->toBe($frozen);
    $thawed = CachedItems::thaw($frozen);
    expect($thawed[0]['category']->is($faq))->toBeTrue()
        ->and($thawed[0]['posts'])->toBeInstanceOf(Collection::class)
        ->and($thawed['plain'])->toBe(3);
});

it('uses the theme default without an own active homepage, and can start from the main one (FR-HOME-08/10)', function () {
    $main = ($this->layoutWith)('site:0', [['section_type' => 'core.spacer'], ['section_type' => 'core.cta', 'settings' => ['button_url' => '/apply']]]);

    expect($this->homepages->forOwner(($this->k)($this->cse)))->toBeNull();

    $copy = $this->homepages->create(($this->k)($this->cse), $main);
    expect($copy->sections()->pluck('section_type')->all())->toBe(['core.spacer', 'core.cta']);

    $copy->update(['is_active' => false]);
    expect($this->homepages->forOwner(($this->k)($this->cse)))->toBeNull();
});

it('follows each section\'s schedule (FR-HOME-06)', function () {
    ($this->layoutWith)('site:0', [
        ['section_type' => 'core.spacer', 'visible_until' => now()->subDay()],
        ['section_type' => 'core.cta', 'visible_from' => now()->addDay(), 'settings' => ['button_url' => '/a']],
        ['section_type' => 'core.rich-text', 'visible_from' => now()->subDay(), 'visible_until' => now()->addDay(), 'settings' => ['body' => ['en' => '<p>Hi</p>']]],
    ]);

    expect(keysOf($this->homepages->forOwner('site:0')))->toBe(['core.rich-text'])
        ->and($this->homepages->forOwner('site:0', 'en')[0]['items'])->toBe('<p>Hi</p>');
});

it('gives core sections their data: hero slides, counters, picture + text', function () {
    Storage::fake('public');
    $photo = app(MediaLibrary::class)->store(UploadedFile::fake()->image('campus.jpg'));
    $set = (new StatSet(['name' => ['en' => 'CSE']]))->setOwnerKey(($this->k)($this->cse));
    $set->save();
    StatItem::create(['stat_set_id' => $set->id, 'label' => ['en' => 'Students'], 'source' => 'manual', 'manual_value' => 1200]);

    ($this->layoutWith)(($this->k)($this->cse), [
        ['section_type' => 'core.hero', 'settings' => ['slides' => [['image' => $photo->id, 'title' => ['en' => 'Welcome'], 'button_url' => '/admission', 'button_label' => ['en' => 'Apply']]]]],
        ['section_type' => 'core.statistics', 'settings' => ['set' => 'default']],
    ]);

    $page = $this->homepages->forOwner(($this->k)($this->cse), 'en');
    expect($page[0]['items'][0])->toMatchArray(['title' => 'Welcome', 'button' => ['label' => 'Apply', 'url' => '/admission']])
        ->and($page[1]['items'][0])->toMatchArray(['label' => 'Students', 'display' => '1,200'])
        ->and($photo->refresh()->usage_count)->toBe(1);                                              // the slide's picture is in use
});

it('builds and reorders a homepage on screen (AC-HOME-01)', function () {
    $this->actingAs(superAdmin());

    $page = Livewire::withQueryParams(['owner' => 'site:0'])->test(ManageHomepage::class)
        ->assertSee(__('homepage::homepage.empty'))
        ->call('createOwn');

    $page->set('data.sections', [
        ['section_type' => 'core.cta', 'heading' => ['en' => 'Admission open'], 'settings' => ['button_url' => '/apply', 'background' => 'primary'], 'is_active' => true],
        ['section_type' => 'faq.highlights', 'heading' => ['en' => 'FAQ'], 'settings' => ['source_mode' => 'latest', 'count' => 3], 'is_active' => true],
    ])->call('save');
    expect(keysOf($this->homepages->forOwner('site:0')))->toBe(['core.cta', 'faq.highlights']);

    $sections = $this->homepages->own('site:0')->sections()->get();
    $page->set('data.sections', [
        ['id' => $sections[1]->id, 'section_type' => 'faq.highlights', 'settings' => ['source_mode' => 'latest', 'count' => 3], 'is_active' => true],
        ['id' => $sections[0]->id, 'section_type' => 'core.cta', 'settings' => ['button_url' => '/apply'], 'is_active' => true],
    ])->call('save');

    expect(keysOf($this->homepages->forOwner('site:0')))->toBe(['faq.highlights', 'core.cta'])     // dragged up
        ->and(HomeSection::count())->toBe(2);
});

it('keeps custom HTML for Super Admins and homepages within the user\'s entities', function () {
    $this->actingAs(makeUser())->get(ManageHomepage::getUrl())->assertForbidden();

    $user = makeUser(['Manage:Homepage'], [$this->cse]);
    expect(MicrositeOwners::keys($user, 'Homepage'))->toBe([($this->k)($this->cse)]);
    $this->actingAs($user);

    $page = Livewire::withQueryParams(['owner' => 'site:0'])->test(ManageHomepage::class)
        ->assertSet('owner', ($this->k)($this->cse))
        ->call('createOwn');
    $page->set('data.sections', [['section_type' => 'core.html', 'settings' => ['html' => '<script>x</script>'], 'is_active' => true]])->call('save');

    expect(HomeSection::first()->settings['html'] ?? null)->toBeNull();
});

it('creates the main homepage\'s default sections once (FR-HOME-12)', function () {
    (new HomepageDatabaseSeeder)->run();
    (new HomepageDatabaseSeeder)->run();

    expect(HomeSection::pluck('section_type')->all())
        ->toBe(['core.hero', 'core.statistics', 'program.list', 'news.latest', 'events.upcoming', 'notice.board', 'testimonials.list', 'partner.list']);
});

it('renders the homepage sections through the active theme; none → the theme\'s default (C-06, FR-HOME-08)', function () {
    $this->get('/')->assertOk()->assertSee(settings('general.site_name'));                     // theme default homepage

    ($this->layoutWith)('site:0', [
        ['section_type' => 'faq.highlights', 'heading' => ['en' => 'Common questions'], 'subheading' => ['en' => 'Asked most often']],
        ['section_type' => 'core.spacer'],
    ]);
    Faq::factory()->create(['question' => 'How do I apply?']);

    $this->get('/')->assertOk()
        ->assertSeeInOrder(['Common questions', 'Asked most often'])
        ->assertSee('home-section', false);
});
