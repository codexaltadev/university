<?php

/*
| FAQ list for hundreds of questions across faculties, departments, programs and
| offices: filters by place (with what is below), main site, category, state
| (FR-GEN-21, FR-REL-04, FR-CNT-C05).
*/

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Faq\Filament\Resources\Faqs\Pages\ListFaqs;
use Modules\Faq\Models\Faq;
use Modules\Office\Models\Office;
use Platform\Modules\ModulePermissions;
use Platform\Relations\RelationEngine;
use Platform\Relations\Targets;
use Platform\Scope\UserScopeFilter;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Faq');

    $this->engineering = faculty();
    $this->cse = department($this->engineering, 'CSE');
    $this->eee = department($this->engineering, 'EEE');
    $this->office = Office::create(['name' => 'Registrar Office']);

    $k = fn ($m) => $m->getMorphClass().':'.$m->id;
    $this->key = $k;
    $faq = function (string $question, array $places, bool $main = false) {
        $faq = Faq::factory()->create(['question' => $question]);
        app(RelationEngine::class)->sync($faq, $places, mainSite: $main, user: null);

        return $faq;
    };

    $this->inCse = $faq('CSE lab hours?', [$k($this->cse)]);
    $this->inEee = $faq('EEE lab hours?', [$k($this->eee)]);
    $this->inFaculty = $faq('Engineering dean?', [$k($this->engineering)]);
    $this->inOffice = $faq('Transcript fee?', [$k($this->office)]);
    $this->onMain = $faq('How to apply?', [$k($this->cse)], main: true);
});

it('filters by place, with or without what is below it (FR-REL-04)', function () {
    $this->actingAs(superAdmin());
    $engineering = ($this->key)($this->engineering);

    Livewire::test(ListFaqs::class)
        ->filterTable('shown_in', ['places' => [$engineering], 'with_children' => true])
        ->assertCanSeeTableRecords([$this->inCse, $this->inEee, $this->inFaculty, $this->onMain])
        ->assertCanNotSeeTableRecords([$this->inOffice])
        ->filterTable('shown_in', ['places' => [$engineering], 'with_children' => false])
        ->assertCanSeeTableRecords([$this->inFaculty])
        ->assertCanNotSeeTableRecords([$this->inCse, $this->inEee, $this->inOffice])
        ->filterTable('shown_in', ['places' => [($this->key)($this->office)], 'with_children' => true])
        ->assertCanSeeTableRecords([$this->inOffice])
        ->assertCanNotSeeTableRecords([$this->inCse, $this->inFaculty]);
});

it('filters by main site, category (sub-categories included) and state', function () {
    $this->actingAs(superAdmin());
    $admission = Term::create(['vocabulary_id' => app(Vocabularies::class)->find('faq.category')->id, 'name' => ['en' => 'Admission']]);
    $undergraduate = Term::create(['vocabulary_id' => $admission->vocabulary_id, 'parent_id' => $admission->id, 'name' => ['en' => 'Undergraduate']]);
    $this->inCse->syncTerms('faq.category', [$admission->id]);
    $this->inEee->syncTerms('faq.category', [$undergraduate->id]);
    $this->inOffice->update(['is_active' => false]);

    Livewire::test(ListFaqs::class)
        ->filterTable('main_site', true)
        ->assertCanSeeTableRecords([$this->onMain])
        ->assertCanNotSeeTableRecords([$this->inCse])
        ->resetTableFilters()
        ->filterTable('category', [$admission->id])
        ->assertCanSeeTableRecords([$this->inCse, $this->inEee])
        ->assertCanNotSeeTableRecords([$this->inFaculty])
        ->resetTableFilters()
        ->filterTable('is_active', false)
        ->assertCanSeeTableRecords([$this->inOffice])
        ->assertCanNotSeeTableRecords([$this->inCse]);
});

it('shows where each question appears, main site first', function () {
    $this->actingAs(superAdmin());

    Livewire::test(ListFaqs::class)
        ->assertTableColumnStateSet('shown_in', ['🌐 Main site', 'CSE'], $this->onMain)
        ->assertTableColumnStateSet('shown_in', ['Registrar Office'], $this->inOffice);
});

it('gives entity users only their own questions and places', function () {
    $user = makeUser(['ViewAny:Faq'], [$this->cse]);
    $this->actingAs($user);
    // In the browser the panel middleware switches the scope filter on; unit tests skip middleware
    UserScopeFilter::enforce();

    try {
        Livewire::test(ListFaqs::class)
            ->assertCanSeeTableRecords([$this->inCse, $this->onMain])
            ->assertCanNotSeeTableRecords([$this->inEee, $this->inFaculty, $this->inOffice]);

        expect(collect(app(Targets::class)->options($user))->flatMap(fn ($group) => array_keys($group))->all())
            ->toBe([($this->key)($this->cse)]);
    } finally {
        UserScopeFilter::enforce(false);
    }
});

it('keeps deleted questions in the trash, restorable (FR-GEN-02)', function () {
    $this->actingAs(superAdmin());
    $this->inCse->delete();

    Livewire::test(ListFaqs::class)
        ->assertCanNotSeeTableRecords([$this->inCse])
        ->filterTable('trashed', false)
        ->assertCanSeeTableRecords([$this->inCse])
        ->callTableAction('restore', $this->inCse);

    expect($this->inCse->refresh()->trashed())->toBeFalse();
});
