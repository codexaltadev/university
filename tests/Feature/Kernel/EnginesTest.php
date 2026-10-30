<?php

/*
| Phase 1C — morph map, hierarchy, Relation engine (P-01), Assignment engine (P-02),
| AppliesUserScope (SAD Part B §1.3–§5, DB-01 §8). Test Plan TC-REL, TC-ASG, TC-PERM.
*/

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Modules\Faq\Filament\Resources\Faqs\Pages\ListFaqs;
use Modules\Faq\Models\Faq;
use Modules\Office\Models\Office;
use Platform\Activity\Activity;
use Platform\Assignments\AssignmentEngine;
use Platform\Assignments\AssignmentRole;
use Platform\Assignments\ManualPerson;
use Platform\Assignments\RoleCatalog;
use Platform\Events\ContentHighlightChanged;
use Platform\Hierarchy\EntityHierarchy;
use Platform\Modules\ModulePermissions;
use Platform\Relations\ContentRelation;
use Platform\Relations\MainSiteRequest;
use Platform\Relations\RelationEngine;
use Platform\Relations\RelationRejected;
use Platform\Scope\UserScopeFilter;
use Platform\Support\MorphMap;
use Tests\Fixtures\Models\FixtureChild;

beforeEach(function () {
    $this->engineering = faculty('Engineering');
    $this->cse = department($this->engineering, 'CSE');
    $this->eee = department($this->engineering, 'EEE');
    $this->business = faculty('Business');
    $this->bba = department($this->business, 'BBA');

    app(ModulePermissions::class)->sync('Faq');
    app(ModulePermissions::class)->syncAttachable('faq', [Faq::class]);
    app(RoleCatalog::class)->sync();
});

afterEach(fn () => UserScopeFilter::enforce(false));

function relations(): RelationEngine
{
    return app(RelationEngine::class);
}

function keyOf(Model $m): string
{
    return $m->getMorphClass().':'.$m->getKey();
}

/** @return list<string> */
function rejection(Closure $call): array
{
    try {
        $call();
    } catch (RelationRejected $e) {
        return $e->errors;
    }

    test()->fail('Expected the change to be refused.');
}

// ─── Morph map (ADR-17) ─────────────────────────────────────────────────────

it('stores aliases, not class names, in polymorphic columns', function () {
    $faq = Faq::factory()->shownIn($this->cse)->create();
    superAdmin();

    expect(MorphMap::alias(Faq::class))->toBe('faq.faq')
        ->and(ContentRelation::first()->content_type)->toBe('faq.faq')
        ->and(Activity::where('subject_id', $faq->id)->where('subject_type', 'faq.faq')->exists())->toBeTrue()   // the user may share the id
        ->and(Activity::where('subject_type', Faq::class)->exists())->toBeFalse()
        ->and(DB::table('model_has_roles')->value('model_type'))->toBe('core.user');
});

// ─── Hierarchy (§2) ────────────────────────────────────────────────────────

it('knows ancestors and descendants, and refreshes when entities change', function () {
    $hierarchy = app(EntityHierarchy::class);

    expect($hierarchy->ancestors(keyOf($this->cse)))->toBe([keyOf($this->engineering)])
        ->and($hierarchy->descendants(keyOf($this->engineering)))->toEqualCanonicalizing([keyOf($this->cse), keyOf($this->eee)]);

    $ce = department($this->engineering, 'CE');

    expect($hierarchy->descendants(keyOf($this->engineering)))->toContain(keyOf($ce));

    // Move EEE to Business (EntityHierarchyChanged)
    $this->eee->update(['parent_id' => $this->business->id]);
    expect($hierarchy->parent(keyOf($this->eee)))->toBe(keyOf($this->business));
});

// ─── Relation engine: targets (FR-GEN-50/51) ──────────────────────────────

it('saves "Show in" targets and rejects unknown, missing or out-of-scope ones', function () {
    $faq = Faq::factory()->create();

    relations()->sync($faq, [keyOf($this->cse), keyOf($this->eee)]);
    expect($faq->targetKeys())->toEqualCanonicalizing([keyOf($this->cse), keyOf($this->eee)]);

    relations()->sync($faq, [keyOf($this->cse)]);
    expect($faq->targetKeys())->toBe([keyOf($this->cse)]);

    expect(implode(' ', rejection(fn () => relations()->sync($faq, ['nothing.here:1']))))->toContain('not something content can be shown in')
        ->and(implode(' ', rejection(fn () => relations()->sync($faq, ['test.child:999']))))->toContain('does not exist');

    $cseEditor = makeUser(scopes: [$this->cse]);
    expect(implode(' ', rejection(fn () => relations()->sync($faq, [keyOf($this->bba)], user: $cseEditor))))->toContain('outside your scope')
        ->and(implode(' ', rejection(fn () => relations()->sync($faq, [], user: $cseEditor))))->toContain('at least one');

    $dean = makeUser(scopes: [$this->engineering]);
    relations()->sync($faq, [keyOf($this->eee)], user: $dean);   // a descendant of the dean's faculty
    expect($faq->targetKeys())->toBe([keyOf($this->eee)]);
});

// ─── Relation engine: main site (BRD Q-26, FR-CNT-H07/H09) ────────────────

it('lets only PublishMain holders decide the main site, and keeps their decision', function () {
    $faq = Faq::factory()->create();
    $cseEditor = makeUser(['Update:Faq'], [$this->cse]);
    $admin = makeUser(['PublishMain:Faq']);

    relations()->sync($faq, [keyOf($this->cse)], mainSite: true, user: $cseEditor);
    expect($faq->isOnMainSite())->toBeFalse();   // silently ignored without PublishMain

    relations()->sync($faq, [keyOf($this->cse)], mainSite: true, user: $admin);
    expect($faq->isOnMainSite())->toBeTrue();

    // The entity user saves the FAQ again: the Admin's decision stays
    relations()->sync($faq, [keyOf($this->cse)], mainSite: false, user: $cseEditor);
    expect($faq->isOnMainSite())->toBeTrue()
        ->and($faq->targetKeys())->toBe([keyOf($this->cse)]);   // site is never a target

    expect(fn () => relations()->removeFromMainSite($faq, $cseEditor))->toThrow(RelationRejected::class);
    relations()->removeFromMainSite($faq, $admin);
    expect($faq->isOnMainSite())->toBeFalse();
});

it('handles main-site requests from entity users', function () {
    $faq = Faq::factory()->shownIn($this->cse)->create();
    $cseEditor = makeUser(['Update:Faq'], [$this->cse]);
    $admin = makeUser(['PublishMain:Faq']);

    $request = relations()->requestMainSite($faq, $cseEditor);
    expect($request->decision)->toBe('pending')
        ->and(relations()->requestMainSite($faq, $cseEditor)->id)->toBe($request->id);   // one pending at a time

    relations()->publishToMainSite($faq, $admin);
    expect($request->fresh()->decision)->toBe('published')
        ->and($request->fresh()->decided_by)->toBe($admin->id);

    $other = Faq::factory()->shownIn($this->cse)->create();
    relations()->dismissRequest(relations()->requestMainSite($other, $cseEditor), $admin);
    expect(MainSiteRequest::where('content_id', $other->id)->value('decision'))->toBe('dismissed');
});

// ─── Highlight (FR-CNT-H01..H03) ──────────────────────────────────────────

it('keeps highlights per context; main-site highlight needs HighlightMain', function () {
    Event::fake([ContentHighlightChanged::class]);
    $faq = Faq::factory()->create();
    $cseEditor = makeUser(['Update:Faq'], [$this->cse]);
    $admin = makeUser(['PublishMain:Faq', 'HighlightMain:Faq']);

    relations()->sync($faq, [keyOf($this->cse)], mainSite: true, user: $admin);
    relations()->sync($faq, [keyOf($this->cse)], highlights: [keyOf($this->cse) => ['order' => 1], RelationEngine::SITE_KEY => ['order' => 1]], user: $cseEditor);

    $byContext = fn () => ContentRelation::where('content_id', $faq->id)->get()->mapWithKeys(fn ($r) => [$r->targetKey() => $r->is_highlighted]);
    expect($byContext())->toEqual(collect([keyOf($this->cse) => true, RelationEngine::SITE_KEY => false]));

    relations()->sync($faq, [keyOf($this->cse)], highlights: [RelationEngine::SITE_KEY => ['order' => 2, 'until' => now()->addDay()]], user: $admin);
    expect($byContext()[RelationEngine::SITE_KEY])->toBeTrue();

    Event::assertDispatched(ContentHighlightChanged::class);
});

it('expired highlights stop counting at once and are switched off by the hourly job', function () {
    $faq = Faq::factory()->shownIn($this->cse)->create();
    relations()->sync($faq, [keyOf($this->cse)], highlights: [keyOf($this->cse) => ['order' => 1, 'until' => now()->subMinute()]]);

    expect(relations()->for(keyOf($this->cse))->content(Faq::class)->highlighted(5))->toHaveCount(0);

    $this->artisan('uwms:highlights:expire')->assertSuccessful();
    expect(ContentRelation::where('content_id', $faq->id)->value('is_highlighted'))->toBeFalse();
});

// ─── Reading a context (§3.3–3.4) ─────────────────────────────────────────

it('reads a context in the four homepage modes, visible content only', function () {
    // FAQ has no publish date (DB-05 v1.1): newest = last added
    $old = Faq::factory()->shownIn($this->cse)->create();
    $mid = Faq::factory()->shownIn($this->cse)->create();
    $new = Faq::factory()->shownIn($this->cse)->create();
    Faq::factory()->shownIn($this->cse)->create(['is_active' => false]);
    Faq::factory()->shownIn($this->eee)->create();

    relations()->sync($old, [keyOf($this->cse)], highlights: [keyOf($this->cse) => ['order' => 1]]);
    $cse = relations()->for(keyOf($this->cse))->content(Faq::class);

    expect($cse->latest(10)->modelKeys())->toBe([$new->id, $mid->id, $old->id])
        ->and($cse->highlighted(10)->modelKeys())->toBe([$old->id])
        ->and($cse->highlightedFirst(2)->modelKeys())->toBe([$old->id, $new->id])
        ->and($cse->manual([$mid->id, 999, $old->id])->modelKeys())->toBe([$mid->id, $old->id])
        ->and($cse->mode('highlighted_first', 3)->modelKeys())->toBe([$old->id, $new->id, $mid->id]);
});

it('a faculty context can include its departments; the main site shows only site:0 content', function () {
    $cseFaq = Faq::factory()->shownIn($this->cse)->create();
    $facultyFaq = Faq::factory()->shownIn($this->engineering)->create();
    $mainFaq = Faq::factory()->shownIn($this->bba)->onMainSite()->create();

    $faculty = relations()->for(keyOf($this->engineering))->content(Faq::class);

    expect($faculty->latest(10)->modelKeys())->toBe([$facultyFaq->id])
        ->and($faculty->includeDescendants()->latest(10)->modelKeys())->toEqualCanonicalizing([$cseFaq->id, $facultyFaq->id])
        ->and(relations()->for('site')->content(Faq::class)->latest(10)->modelKeys())->toBe([$mainFaq->id]);
});

it('removes relations when content or target is deleted permanently', function () {
    $faq = Faq::factory()->shownIn($this->cse)->onMainSite()->create();
    $faq->delete();
    expect(ContentRelation::where('content_id', $faq->id)->count())->toBe(2);   // soft delete keeps them

    $faq->forceDelete();
    expect(ContentRelation::where('content_id', $faq->id)->count())->toBe(0);

    $other = Faq::factory()->shownIn($this->bba)->create();
    $this->bba->forceDelete();
    expect(ContentRelation::where('content_id', $other->id)->count())->toBe(0);
});

// ─── Assignment engine & Role Catalog (§4, FR-REL-09..11) ─────────────────

it('seeds the role catalog from registrations with standard labels; keys never change', function () {
    $head = AssignmentRole::where('assignable_type', 'test.child')->where('role_key', 'head')->first();

    expect($head->label)->toBe('Head')
        ->and($head->is_single_holder)->toBeTrue()
        ->and(AssignmentRole::where('assignable_type', 'test.child')->orderBy('sort_order')->pluck('role_key')->all())
        ->toBe(['head', 'coordinator', 'officer']);

    $head->update(['label' => ['en' => 'Chairperson']]);
    app(RoleCatalog::class)->sync();
    expect($head->fresh()->label)->toBe('Chairperson');   // relabel survives a re-sync

    expect(fn () => $head->update(['role_key' => 'chair']))->toThrow(LogicException::class);
});

it('assigns people in catalog roles and lists them for the website', function () {
    $engine = app(AssignmentEngine::class);
    $rahim = ManualPerson::create(['name' => ['en' => 'Rahim'], 'phone' => '0171']);
    $karim = ManualPerson::create(['name' => ['en' => 'Karim']]);
    $salma = ManualPerson::create(['name' => ['en' => 'Salma']]);
    $hidden = ManualPerson::create(['name' => ['en' => 'Hidden']]);
    $gone = ManualPerson::create(['name' => ['en' => 'Gone'], 'is_active' => false]);

    $engine->assign($karim, $this->cse, 'officer');
    $engine->assign($salma, $this->cse, 'coordinator');
    $engine->assign($rahim, $this->cse, 'head', ['overrides' => ['phone' => 'ext 245'], 'role_label' => ['en' => 'Professor & Head']]);
    $engine->assign($hidden, $this->cse, 'officer', ['is_public' => false]);
    $engine->assign($gone, $this->cse, 'officer');

    $people = $this->cse->people();

    expect($people->map->name()->all())->toBe(['Rahim', 'Salma', 'Karim'])   // catalog order, hidden / inactive left out
        ->and($people[0]->roleLabel)->toBe('Professor & Head')
        ->and($people[0]->phone())->toBe('ext 245')                         // assignment override
        ->and($people[1]->roleLabel)->toBe('Coordinator');

    expect(fn () => $engine->assign($karim, $this->cse, 'dean'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $engine->assign($karim, User::factory()->create(), 'head'))->toThrow(InvalidArgumentException::class);
});

it('single-holder roles offer to end the previous holder, who then appears as former', function () {
    $engine = app(AssignmentEngine::class);
    $old = ManualPerson::create(['name' => ['en' => 'Old Head']]);
    $new = ManualPerson::create(['name' => ['en' => 'New Head']]);

    $engine->assign($old, $this->cse, 'head', ['starts_on' => now()->subYears(3)]);
    $result = $engine->assign($new, $this->cse, 'head');
    expect($result['previous'])->toHaveCount(1);   // the UI proposes ending it

    $engine->assignmentsOf($new)->each->forceDelete();
    $engine->assign($new, $this->cse, 'head', ['starts_on' => now()], endPrevious: true);

    expect($this->cse->people('head')->map->name()->all())->toBe(['New Head'])
        ->and($this->cse->people('head', former: true)->map->name()->all())->toBe(['Old Head']);
});

it('works for module assignables: Office head and team', function () {
    app(RoleCatalog::class)->sync('office');
    $office = Office::create(['name' => 'Registrar Office']);
    $person = ManualPerson::create(['name' => ['en' => 'Registrar']]);

    app(AssignmentEngine::class)->assign($person, $office, 'head');

    expect($office->people('head')->first()->roleLabel)->toBe('Head');

    $person->forceDelete();
    expect($office->assignments()->count())->toBe(0);
});

// ─── AppliesUserScope (FR-GEN-52) ─────────────────────────────────────────

it('admin queries show only records inside the user scope', function () {
    $cseFaq = Faq::factory()->shownIn($this->cse)->create();
    $eeeFaq = Faq::factory()->shownIn($this->eee)->create();
    $bbaFaq = Faq::factory()->shownIn($this->bba)->create();
    UserScopeFilter::enforce();

    $this->actingAs(makeUser(scopes: [$this->cse]));
    expect(Faq::pluck('id')->all())->toBe([$cseFaq->id])
        ->and(FixtureChild::pluck('code')->all())->toBe(['CSE']);       // "self" path

    $this->actingAs(makeUser(scopes: [$this->engineering]));
    expect(Faq::pluck('id')->all())->toEqualCanonicalizing([$cseFaq->id, $eeeFaq->id]);

    $this->actingAs(makeUser());                                          // unscoped
    expect(Faq::count())->toBe(3);

    $this->actingAs(superAdmin());
    expect(Faq::count())->toBe(3);
});

it('the scope filter does not touch the public website', function () {
    Faq::factory()->shownIn($this->cse)->create();
    Faq::factory()->shownIn($this->bba)->create();

    $this->actingAs(makeUser(scopes: [$this->cse]));   // logged-in editor browsing the site

    expect(Faq::count())->toBe(2);
});

it('the admin FAQ list shows a CSE editor only CSE FAQs', function () {
    app(ModulePermissions::class)->sync('Faq');
    $cseFaq = Faq::factory()->shownIn($this->cse)->create();
    $bbaFaq = Faq::factory()->shownIn($this->bba)->create();

    $this->actingAs(makeUser(['ViewAny:Faq', 'View:Faq'], [$this->cse]));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setServingStatus(true);

    Livewire::test(ListFaqs::class)
        ->assertCanSeeTableRecords([$cseFaq])
        ->assertCanNotSeeTableRecords([$bbaFaq]);
});
