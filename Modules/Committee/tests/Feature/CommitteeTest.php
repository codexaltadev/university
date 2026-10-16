<?php

/*
| Phase 3.11 — M-16 Committee (DB-04 §7 v1.4, SRS People FR-COM-01..07): committees shown on
| the main site or only in an entity (AC-COM-02), members from any directory with committee
| roles and notes, a member's new designation everywhere (AC-COM-01), terms and "Copy to new
| term", /committees and /{entity}/committees, search and sitemap.
*/

use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Modules\Administration\Models\AdministrationCategory;
use Modules\Administration\Models\Member;
use Modules\Committee\Database\Seeders\CommitteeDatabaseSeeder;
use Modules\Committee\Filament\Resources\Committees\Pages\CreateCommittee;
use Modules\Committee\Filament\Resources\Committees\Pages\EditCommittee;
use Modules\Committee\Models\Committee;
use Modules\Office\Models\Office;
use Platform\Assignments\AssignmentEngine;
use Platform\Assignments\ManualPerson;
use Platform\Assignments\RoleCatalog;
use Platform\Filament\Components\PersonPicker;
use Platform\Modules\ModulePermissions;
use Platform\Relations\RelationEngine;
use Platform\Seo\Sitemap;
use Platform\Taxonomy\Vocabularies;

beforeEach(function () {
    Carbon::setTestNow('2026-03-01 10:00:00');
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    foreach (['Office', 'Administration', 'Committee'] as $module) {
        app(ModulePermissions::class)->sync($module);
    }
    app(RoleCatalog::class)->sync();
    (new CommitteeDatabaseSeeder)->run();
    $this->statutory = (int) array_search('Statutory Bodies', app(Vocabularies::class)->options(Committee::CATEGORY), true);

    $category = AdministrationCategory::query()->create(['name' => ['en' => 'Management'], 'group' => 'management', 'is_public' => true, 'is_active' => true]);
    $this->vc = Member::factory()->in($category)->create(['name' => ['en' => 'Prof. Kabir'], 'designation' => ['en' => 'Vice Chancellor']]);
    $this->registrar = Member::factory()->in($category)->create(['name' => ['en' => 'Dr. Nadir'], 'designation' => ['en' => 'Registrar']]);
    $this->ugc = ManualPerson::query()->create(['name' => ['en' => 'Prof. Talukder'], 'designation' => ['en' => 'University of Dhaka'], 'is_active' => true]);
    $this->lab = Office::create(['name' => ['en' => 'Robotics Lab']]);   // any microsite entity

    $engine = app(AssignmentEngine::class);
    $this->syndicate = Committee::factory()->create(['name' => ['en' => 'Syndicate'], 'category_term_id' => $this->statutory, 'sort_order' => 1, 'term_starts_on' => '2024-01-01']);
    app(RelationEngine::class)->sync($this->syndicate, [], mainSite: true, user: null);
    $engine->assign($this->vc, $this->syndicate, 'chairperson', ['role_label' => ['en' => 'President of the Syndicate']]);
    $engine->assign($this->ugc, $this->syndicate, 'member', ['note' => ['en' => 'Nominated by UGC']]);
    $engine->assign($this->registrar, $this->syndicate, 'member_secretary');

    $this->labCommittee = Committee::factory()->create(['name' => ['en' => 'Robotics Lab Committee'], 'sort_order' => 2]);
    app(RelationEngine::class)->sync($this->labCommittee, ['office.office:'.$this->lab->id], mainSite: false, user: null);
    $engine->assign($this->registrar, $this->labCommittee, 'convener');
});

afterEach(fn () => Carbon::setTestNow());

it('lists main-site committees by category with their head, members in role order with notes (FR-COM-02/03/06)', function () {
    $this->get('/committees')->assertOk()
        ->assertSeeInOrder(['Statutory Bodies', 'Syndicate', 'Prof. Kabir'])
        ->assertDontSee('Robotics Lab Committee');                                                 // only in the lab (AC-COM-02)

    $this->get('/committees/syndicate')->assertOk()
        ->assertSeeInOrder(['Prof. Kabir', 'President of the Syndicate', 'Prof. Talukder', 'Nominated by UGC', 'Dr. Nadir', 'Member Secretary'])
        ->assertSee('"@type":"Organization"', false);
    $this->get('/committees/'.$this->labCommittee->slug())->assertNotFound();
});

it('shows an entity\'s committee only on its microsite (FR-COM-04, AC-COM-02)', function () {
    $base = rtrim($this->lab->url(), '/');
    $this->get($base.'/committees')->assertOk()->assertSee('Robotics Lab Committee')->assertSee('Dr. Nadir')->assertDontSee('Syndicate');
    $this->get($base.'/committees/'.$this->labCommittee->slug())->assertOk()->assertSee('Convener');
    expect($this->labCommittee->url())->toBe($base.'/committees/'.$this->labCommittee->slug());
});

it('shows a member\'s new designation in every committee (AC-COM-01)', function () {
    $this->registrar->update(['designation' => ['en' => 'Registrar (Acting)']]);
    $this->get('/committees/syndicate')->assertOk()->assertSee('Registrar (Acting)');
    expect($this->labCommittee->people()->first()->designation())->toBe('Registrar (Acting)');
});

it('adds a committee with members and a note from the admin (FR-COM-01/02)', function () {
    $this->actingAs(superAdmin());
    Livewire::test(CreateCommittee::class)
        ->fillForm([
            'name' => ['en' => 'Finance Committee'], 'category_term_id' => $this->statutory, 'show_in' => [], 'main_site' => true,
            'people' => [
                ['person' => PersonPicker::keyOf($this->vc), 'role_key' => 'chairperson', 'is_public' => true, 'note' => ['en' => 'Ex officio']],
                ['person' => PersonPicker::keyOf($this->registrar), 'role_key' => 'member_secretary', 'is_public' => true],
            ],
        ])
        ->call('create')->assertHasNoFormErrors();

    $finance = Committee::query()->get()->first(fn (Committee $c): bool => $c->name === 'Finance Committee');
    expect($finance->isOnMainSite())->toBeTrue()
        ->and($finance->people()->map->name()->all())->toBe(['Prof. Kabir', 'Dr. Nadir'])
        ->and($finance->people()->first()->note())->toBe('Ex officio');
});

it('starts a new term with today\'s members and keeps the old one as a previous term (FR-COM-05)', function () {
    $next = $this->syndicate->copyToNewTerm(Carbon::parse('2026-04-01'), Carbon::parse('2028-03-31'));

    expect($this->syndicate->fresh()->term_ends_on->toDateString())->toBe('2026-03-31')
        ->and($next->previous_term_id)->toBe($this->syndicate->id)
        ->and($next->isOnMainSite())->toBeTrue()
        ->and($next->assignments()->count())->toBe(3)
        ->and($next->people()->first()->roleLabel)->toBe('President of the Syndicate');

    Carbon::setTestNow('2026-05-01');
    $this->get('/committees')->assertOk()->assertSee($next->slug())->assertDontSee('/committees/syndicate"', false);   // only the running term listed
    $this->get('/committees/'.$next->slug())->assertOk()->assertSee('Previous terms')->assertSee('/committees/syndicate', false);
    $this->get('/committees/syndicate')->assertOk()->assertSee('Previous term')->assertSee('See the current committee');

    $this->actingAs(superAdmin());
    Livewire::test(EditCommittee::class, ['record' => $this->syndicate->id])->assertActionHidden('newTerm');   // already has a next term
});

it('puts committees in search and the sitemap', function () {
    expect(collect(app(Sitemap::class)->entries())->pluck('loc')->implode(' '))->toContain('/committees/syndicate')
        ->and($this->syndicate->toSearchDocument('en'))->toMatchArray(['title' => 'Syndicate']);
});
