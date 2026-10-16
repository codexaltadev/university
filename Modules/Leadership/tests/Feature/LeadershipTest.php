<?php

/*
| Phase 3.10 — M-15 Leadership (DB-04 §6 v1.3, SRS People FR-LEAD-01..07): positions in
| groups, people from any directory or manual, terms with message and biography, one current
| holder of a single-holder post (AC-LEAD-01), /leadership, /leadership/former, profile pages,
| the homepage message section, search and sitemap.
*/

use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Modules\Administration\Models\AdministrationCategory;
use Modules\Administration\Models\Member;
use Modules\Leadership\Database\Seeders\LeadershipDatabaseSeeder;
use Modules\Leadership\Filament\Resources\Positions\Pages\EditPosition;
use Modules\Leadership\Filament\Resources\Positions\RelationManagers\TermsRelationManager;
use Modules\Leadership\Models\Position;
use Modules\Leadership\Sections\LeadershipMessageSection;
use Modules\Leadership\Support\Appointments;
use Modules\Menu\Support\MenuLinks;
use Platform\Assignments\ManualPerson;
use Platform\Assignments\RoleCatalog;
use Platform\Filament\Components\PersonPicker;
use Platform\Homepage\SectionContext;
use Platform\Modules\ModulePermissions;
use Platform\Seo\Sitemap;
use Platform\Taxonomy\Vocabularies;

beforeEach(function () {
    Carbon::setTestNow('2026-03-01 10:00:00');
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    foreach (['Administration', 'Leadership'] as $module) {
        app(ModulePermissions::class)->sync($module);
    }
    app(RoleCatalog::class)->sync();
    (new LeadershipDatabaseSeeder)->run();

    $group = fn (string $name): int => (int) array_search($name, app(Vocabularies::class)->options(Position::GROUP), true);
    $this->vc = Position::query()->create(['title' => ['en' => 'Vice Chancellor'], 'group_term_id' => $group('Executive'), 'is_single_holder' => true, 'sort_order' => 1, 'is_active' => true]);
    $this->trustee = Position::query()->create(['title' => ['en' => 'Member, Board of Trustees'], 'group_term_id' => $group('Board of Trustees'), 'is_single_holder' => false, 'sort_order' => 2, 'is_active' => true]);

    $officer = AdministrationCategory::query()->create(['name' => ['en' => 'Management'], 'group' => 'management', 'is_public' => true, 'is_active' => true]);
    $this->kabir = Member::factory()->in($officer)->create(['name' => ['en' => 'Prof. Kabir'], 'designation' => ['en' => 'Vice Chancellor']]);
    $this->rahman = Member::factory()->in($officer)->create(['name' => ['en' => 'Prof. Rahman'], 'designation' => ['en' => 'Professor']]);
    $this->trusteeOne = ManualPerson::query()->create(['name' => ['en' => 'Md. Sabur Khan'], 'is_active' => true]);
    $this->trusteeTwo = ManualPerson::query()->create(['name' => ['en' => 'Ms. Sahana Khan'], 'is_active' => true]);

    $this->appoint = app(Appointments::class);
    $this->kabirTerm = $this->appoint->appoint($this->vc, $this->kabir, ['starts_on' => '2022-01-01', 'message' => ['en' => '<p>Welcome to our university of innovation.</p>']]);
    $this->appoint->appoint($this->trustee, $this->trusteeOne, ['starts_on' => '2020-01-01']);
    $this->appoint->appoint($this->trustee, $this->trusteeTwo, ['starts_on' => '2021-01-01']);
});

afterEach(fn () => Carbon::setTestNow());

it('lists today\'s holders by group, with people from a directory and manual ones (FR-LEAD-01..03)', function () {
    $this->get('/leadership')->assertOk()
        ->assertSeeInOrder(['Board of Trustees', 'Md. Sabur Khan', 'Ms. Sahana Khan', 'Executive', 'Prof. Kabir', 'Vice Chancellor']);
    $this->get('/leadership?view=cards')->assertOk()->assertSee('Welcome to our university of innovation.');

    $this->get($this->kabirTerm->url())->assertOk()
        ->assertSee('Prof. Kabir')->assertSee('Vice Chancellor')->assertSee('Since 2022')
        ->assertSee('Welcome to our university of innovation.')
        ->assertSee($this->kabir->url())                                                            // the directory profile
        ->assertSee('"@type":"Person"', false);
});

it('moves the previous Vice Chancellor to Former and shows the new message on the homepage (AC-LEAD-01, FR-LEAD-05/07)', function () {
    expect($this->appoint->currentHolders($this->vc))->toBe(['Prof. Kabir'])
        ->and($this->appoint->currentHolders($this->trustee))->toBe([]);                            // many members: nothing to end

    $new = $this->appoint->appoint($this->vc, $this->rahman, ['starts_on' => '2026-02-01', 'message' => ['en' => '<p>A new chapter begins.</p>']]);
    expect($this->kabirTerm->fresh()->assignment->ends_on->toDateString())->toBe('2026-01-31')
        ->and($this->kabirTerm->fresh()->isFormer())->toBeTrue();

    $this->get('/leadership')->assertOk()->assertSee('Prof. Rahman')->assertDontSee('Prof. Kabir');
    $this->get('/leadership/former')->assertOk()->assertSeeInOrder(['Former Vice Chancellor', 'Prof. Kabir', '2022 – 2026']);
    $this->get($this->kabirTerm->url())->assertOk()->assertSee('Former Vice Chancellor');

    $section = new LeadershipMessageSection;
    $term = $section->items(new SectionContext('site:0', ['position_id' => $this->vc->id], 'en'));
    expect($term->is($new))->toBeTrue();
    expect(view(LeadershipMessageSection::view(), ['items' => $term, 'section' => null, 'settings' => ['words' => 10]])->render())
        ->toContain('Message from the Vice Chancellor')->toContain('A new chapter begins.')->toContain('Prof. Rahman');
});

it('appoints from the position page, offering to end the current term (FR-LEAD-07)', function () {
    $this->actingAs(superAdmin());
    Livewire::test(TermsRelationManager::class, ['ownerRecord' => $this->vc, 'pageClass' => EditPosition::class])
        ->assertSee('Prof. Kabir')
        ->callTableAction('create', data: ['person' => PersonPicker::keyOf($this->rahman), 'starts_on' => '2026-03-01', 'end_previous' => true, 'message' => ['en' => '<p>Hello.</p>'], 'is_public' => true])
        ->assertHasNoTableActionErrors();

    expect($this->vc->terms()->current()->get()->map->name()->all())->toBe(['Prof. Rahman'])
        ->and($this->kabirTerm->fresh()->assignment->ends_on->toDateString())->toBe('2026-02-28');

    Livewire::test(TermsRelationManager::class, ['ownerRecord' => $this->vc, 'pageClass' => EditPosition::class])
        ->filterTable('former', true)->assertCanSeeTableRecords([$this->kabirTerm])
        ->filterTable('former', false)->assertCanNotSeeTableRecords([$this->kabirTerm]);
});

it('hides people who left or terms that are not public', function () {
    $this->kabir->update(['is_active' => false]);
    $this->get('/leadership')->assertOk()->assertDontSee('Prof. Kabir');
    $this->get($this->kabirTerm->url())->assertNotFound();

    $this->kabir->update(['is_active' => true]);
    $this->kabirTerm->assignment->update(['is_public' => false]);
    $this->get('/leadership')->assertOk()->assertDontSee('Prof. Kabir');
});

it('puts terms in search and the sitemap and offers the pages to menus', function () {
    expect(collect(app(Sitemap::class)->entries())->pluck('loc')->implode(' '))->toContain($this->kabirTerm->url())
        ->and($this->kabirTerm->toSearchDocument('en'))->toMatchArray(['title' => 'Prof. Kabir — Vice Chancellor'])
        ->and(app(MenuLinks::class)->modulePagePath('page:leadership.former'))->toBe('/leadership/former');
});
