<?php

/*
| Phase 3.1 — Faculty (M-01): admin, profile-only edit for Faculty users, delete
| guard, counters, website listing and layouts, microsite home, homepage section,
| search and sitemap. FR-FAC-01..09, BRL-FAC-01/02, AC-FAC-01/02.
*/

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Faculty\Filament\Resources\Faculties\FacultyResource;
use Modules\Faculty\Filament\Resources\Faculties\Pages\CreateFaculty;
use Modules\Faculty\Filament\Resources\Faculties\Pages\EditFaculty;
use Modules\Faculty\Models\Faculty;
use Modules\Faculty\Sections\OurFacultiesSection;
use Modules\Faculty\Settings\FacultySettings;
use Platform\Assignments\ManualPerson;
use Platform\Assignments\RoleCatalog;
use Platform\Exceptions\ParentHasActiveChildren;
use Platform\Homepage\SectionContext;
use Platform\Modules\ModulePermissions;
use Platform\Registry\Registry;
use Platform\Search\SearchIndex;
use Platform\Statistics\StatSet;
use Platform\Theme\LayoutResolver;
use Tests\Fixtures\Models\FixtureChild;
use Tests\Fixtures\Models\FixtureParent;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Faculty');

    $this->fsit = Faculty::factory()->create([
        'name' => ['en' => 'Faculty of Science and Information Technology', 'bn' => 'বিজ্ঞান ও তথ্যপ্রযুক্তি অনুষদ'],
        'short_name' => 'FSIT',
        'overview' => ['en' => '<p>Computing and science together.</p>'],
        'dean_message' => ['en' => '<p>Welcome to FSIT.</p>'],
    ]);
});

it('creates a faculty with its address and its own counters (FR-FAC-01/04)', function () {
    $this->actingAs(superAdmin());

    Livewire::test(CreateFaculty::class)
        ->fillForm(['name' => ['en' => 'Faculty of Business and Entrepreneurship', 'bn' => 'ব্যবসায় ও উদ্যোক্তা অনুষদ'], 'short_name' => 'FBE', 'web_address' => 'fbe'])
        ->call('create')
        ->assertHasNoFormErrors();

    $fbe = Faculty::query()->where('short_name', 'FBE')->sole();
    expect($fbe->url())->toBe('/faculties/fbe')
        ->and($fbe->getTranslation('name', 'bn'))->toBe('ব্যবসায় ও উদ্যোক্তা অনুষদ');

    $set = StatSet::query()->ownedBy($fbe->getMorphClass().':'.$fbe->id)->sole();
    expect($set->items()->pluck('metric_key')->filter()->values()->all())->toBe(['department.count', 'program.count', 'faculty-member.count']);

    // Short name is unique
    Livewire::test(CreateFaculty::class)
        ->fillForm(['name' => ['en' => 'Another'], 'short_name' => 'FBE'])
        ->call('create')
        ->assertHasFormErrors(['short_name']);
});

it('lets a Faculty user edit the profile but not the name, short name or address (BRL-FAC-02)', function () {
    $other = Faculty::factory()->create(['short_name' => 'FE']);
    $user = makeUser(['ViewAny:Faculty', 'View:Faculty', 'Update:Faculty'], [$this->fsit]);
    $this->actingAs($user);

    // Outside the user's scope the record does not exist for them (the scope filter hides it)
    $this->get(FacultyResource::getUrl('edit', ['record' => $other]))->assertNotFound();

    Livewire::test(EditFaculty::class, ['record' => $this->fsit->getRouteKey()])
        ->assertFormFieldIsDisabled('short_name')
        ->assertFormFieldIsDisabled('web_address')
        ->fillForm(['overview' => ['en' => '<p>New overview.</p>'], 'phone' => '+880 1234'])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->fsit->refresh();
    expect($this->fsit->short_name)->toBe('FSIT')
        ->and($this->fsit->getTranslation('name', 'en'))->toBe('Faculty of Science and Information Technology')
        ->and($this->fsit->phone)->toBe('+880 1234')
        ->and($this->fsit->getTranslation('overview', 'en'))->toContain('New overview.');
});

it('cannot be deleted while it has active children of another module (BRL-FAC-01)', function () {
    app(Registry::class)->add('relationTarget', 'test', ['model' => FixtureChild::class, 'parent' => Faculty::class, 'parentKey' => 'parent_id']);
    FixtureParent::query()->forceCreate(['id' => $this->fsit->id, 'name' => 'fixture row for the FK']);
    $child = FixtureChild::create(['parent_id' => $this->fsit->id, 'name' => 'CSE', 'code' => 'CSE']);

    try {
        $this->fsit->delete();
        $this->fail('The faculty was deleted.');
    } catch (ParentHasActiveChildren $e) {
        expect($e->children)->toBe(['fixtureChildren' => ['CSE']]);   // the warning lists them
    }

    $child->update(['is_active' => false]);
    $this->fsit->delete();
    expect($this->fsit->trashed())->toBeTrue();
});

it('lists faculties on /faculties in the chosen layout, without hidden ones (FR-FAC-05/07/09)', function () {
    $hidden = Faculty::factory()->create(['name' => ['en' => 'Faculty of Hidden Studies'], 'show_in_listing' => false]);
    $off = Faculty::factory()->create(['name' => ['en' => 'Faculty of Closed Things'], 'is_active' => false]);

    // Through the default theme: DIU's page (cards with the overview), whatever the layout
    $this->get('/faculties')->assertOk()
        ->assertSee('Browse by Faculties')
        ->assertSeeInOrder(['Faculty of Science and Information Technology', 'Computing and science together.', 'Learn More'])
        ->assertSee('href="'.url($this->fsit->url()).'"', false)
        ->assertDontSee('Faculty of Hidden Studies')
        ->assertDontSee('Faculty of Closed Things');

    // The module's own layouts, for themes without their own
    $faculties = Faculty::query()->listed()->get();
    expect(moduleView('Faculty', 'listing.grid', ['faculties' => $faculties, 'options' => []]))->toContain('faculty-card--grid')->not->toContain('Faculty of Hidden Studies');
    app(LayoutResolver::class)->choose('faculty.listing', 'list');
    expect(moduleView('Faculty', 'listing.list', ['faculties' => $faculties, 'options' => []]))->toContain('faculty-card--list', 'Computing and science together.');

    // A hidden one keeps its own address; an inactive one does not
    $this->get($hidden->url())->assertOk();
    $this->get($off->url())->assertNotFound();
});

it('takes the /faculties text and links from Settings › Faculties, and a faculty\'s Quick Links from its form', function () {
    // DIU's links are the defaults
    $this->get('/faculties')->assertOk()->assertSeeInOrder(['Department Heads', 'Faculty Members', 'International Advisor', 'Visiting Professors', 'Quick Links', 'Admission Requirements', 'Transfer Credits']);

    settings()->saveFor(new FacultySettings, [
        'listing_title' => ['en' => 'Our Schools'], 'listing_text' => ['en' => 'Seven schools, one campus.'],
        'side_links' => [['label' => ['en' => 'Visiting Scholars'], 'url' => '/visiting']],
        'quick_links' => [['label' => ['en' => 'Credit Transfer Rules'], 'url' => 'https://example.edu/credits.pdf'], ['label' => ['en' => ''], 'url' => '/no-label']],
    ]);
    $this->get('/faculties')->assertOk()
        ->assertSee('Our Schools')->assertSee('Seven schools, one campus.')
        ->assertSee('Browse by Section (04)')->assertSee('Visiting Scholars')->assertSee('href="'.url('/visiting').'"', false)
        ->assertSee('Credit Transfer Rules')->assertSee('https://example.edu/credits.pdf', false)
        ->assertDontSee('International Advisor')->assertDontSee('/no-label', false);   // a row without text is left out

    // A faculty's own Quick Links, entered with the faculty
    $this->actingAs(superAdmin());
    Livewire::test(EditFaculty::class, ['record' => $this->fsit->getRouteKey()])
        ->fillForm(['quick_links' => [['label' => ['en' => 'FSIT Notices', 'bn' => 'নোটিশ'], 'url' => '/fsit/notices']]])
        ->call('save')->assertHasNoFormErrors();
    expect(link_list($this->fsit->fresh()->quick_links))->toBe([['label' => 'FSIT Notices', 'url' => url('/fsit/notices')]]);

    Livewire::test(EditFaculty::class, ['record' => $this->fsit->getRouteKey()])
        ->fillForm(['quick_links' => [['label' => ['en' => 'Bad'], 'url' => 'javascript:alert(1)']]])
        ->call('save')->assertHasFormErrors();
});

it('shows the deans on DIU\'s /faculties under their faculty, as the Deans tab (Phase 4.4d)', function () {
    app(RoleCatalog::class)->sync();
    $dean = ManualPerson::create(['name' => ['en' => 'Prof. Dr. Touhid Bhuiyan'], 'designation' => ['en' => 'Dean, FSIT']]);
    $this->fsit->assignments()->create(['person_type' => $dean->getMorphClass(), 'person_id' => $dean->id, 'role_key' => 'dean', 'sort_order' => 1, 'is_public' => true]);

    $this->get('/faculties')->assertOk()
        ->assertSeeInOrder(['Browse by Section (06)', 'Faculties', 'Deans', 'Department Heads', 'Faculty Members'])
        ->assertSeeInOrder(['Browse by Deans', 'Faculty of Science and Information Technology', 'Prof. Dr. Touhid Bhuiyan', 'Faculty of Science and Information Technology (Dean)', 'View Profile'])
        ->assertDontSee('No deans available.')
        ->assertSee('No department heads available.');
});

it('shows a default microsite home with overview, Dean and counters (AC-FAC-01, FR-FAC-03/06)', function () {
    app(RoleCatalog::class)->sync();
    $dean = ManualPerson::create(['name' => ['en' => 'Prof. Dr. Touhid Bhuiyan'], 'designation' => ['en' => 'Dean, FSIT']]);
    $this->fsit->assignments()->create(['person_type' => $dean->getMorphClass(), 'person_id' => $dean->id, 'role_key' => 'dean', 'sort_order' => 1, 'is_public' => true]);

    $students = StatSet::query()->ownedBy($this->fsit->getMorphClass().':'.$this->fsit->id)->sole()->items()->where('source', 'manual')->sole();
    $students->update(['manual_value' => 12000]);

    $this->get($this->fsit->url())->assertOk()
        ->assertSee('Faculty of Science and Information Technology')
        ->assertSee('Computing and science together.')
        ->assertSee('Welcome to FSIT.')
        ->assertSee('Prof. Dr. Touhid Bhuiyan')
        ->assertSee('12,000+')
        ->assertSee('microsite-name', false);                    // the theme's microsite layout, main menu fallback

    $this->get('/bn'.$this->fsit->url('bn'))->assertOk()->assertSee('বিজ্ঞান ও তথ্যপ্রযুক্তি অনুষদ');
});

it('offers the "Our Faculties" homepage section, search and sitemap (FR-FAC-08)', function () {
    $section = new OurFacultiesSection;
    $items = $section->items(new SectionContext('site:0', ['limit' => 5], 'en'));
    expect($items->pluck('short_name')->all())->toContain('FSIT');

    expect(app(SearchIndex::class)->search('FSIT')['groups'])->toHaveKey('faculty');

    $this->get('/sitemap.xml')->assertSee(url($this->fsit->url()), false);
});

it('lets only permitted users into the faculty screens', function () {
    $this->actingAs(makeUser());
    $this->get(FacultyResource::getUrl())->assertForbidden();

    $this->actingAs(superAdmin());
    $this->get(FacultyResource::getUrl())->assertOk()->assertSee('FSIT');
});
