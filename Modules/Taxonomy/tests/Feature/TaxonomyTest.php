<?php

/*
| Phase 2.5a — Taxonomy (C-16): registered vocabularies, HasTaxonomy, TermSelect,
| Categories & Tags screen, delete rules (FR-TAX-01..04, BRL-TAX-01).
*/

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Faq\Filament\Resources\Faqs\Pages\CreateFaq;
use Modules\Faq\Filament\Resources\Faqs\Pages\EditFaq;
use Modules\Faq\Models\Faq;
use Modules\Taxonomy\Filament\Resources\Vocabularies\Pages\EditVocabulary;
use Modules\Taxonomy\Filament\Resources\Vocabularies\Pages\ListVocabularies;
use Modules\Taxonomy\Filament\Resources\Vocabularies\RelationManagers\TermsRelationManager;
use Modules\Taxonomy\Filament\Resources\Vocabularies\VocabularyResource;
use Platform\Events\ModuleUninstalling;
use Platform\Exceptions\ParentHasActiveChildren;
use Platform\Modules\Manifest;
use Platform\Modules\ModuleManager;
use Platform\Modules\ModulePermissions;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Platform\Taxonomy\Vocabulary;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Permission::findOrCreate('Manage:Taxonomy', 'web');
});

function vocab(): Vocabularies
{
    return app(Vocabularies::class);
}

function term(string $vocabulary, string $name, ?Term $parent = null): Term
{
    return Term::create(['vocabulary_id' => vocab()->find($vocabulary)->id, 'parent_id' => $parent?->id, 'name' => ['en' => $name]]);
}

it('creates the registered vocabularies, the shared Tags included (FR-TAX-01/03)', function () {
    vocab()->sync();

    $faq = Vocabulary::where('key', 'faq.category')->first();
    $tags = Vocabulary::where('key', 'shared.tags')->first();

    expect($faq->module_slug)->toBe('faq')
        ->and($faq->name)->toBe('FAQ category')
        ->and($faq->is_hierarchical)->toBeTrue()
        ->and($faq->allows_multiple)->toBeFalse()
        ->and($tags->module_slug)->toBeNull()
        ->and($tags->allows_multiple)->toBeTrue();
});

it('adds default terms once, with slugs (Module Guide §13)', function () {
    $terms = [['slug' => 'admission', 'name' => ['en' => 'Admission', 'bn' => 'ভর্তি'], 'children' => [['name' => ['en' => 'Undergraduate']]]]];

    Vocabularies::ensureTerms('faq.category', $terms);
    Vocabularies::ensureTerms('faq.category', $terms);

    $admission = Term::whereJsonContains('name->en', 'Admission')->first();
    expect(Term::count())->toBe(2)
        ->and($admission->slug('en'))->toBe('admission')
        ->and($admission->children()->first()->name)->toBe('Undergraduate');
});

it('links terms per vocabulary; single-choice keeps one (HasTaxonomy)', function () {
    $general = term('faq.category', 'General');
    $fees = term('faq.category', 'Fees');
    $urgent = term('shared.tags', 'urgent');
    $faq = Faq::factory()->create();

    $faq->syncTerms('shared.tags', [$urgent->id]);
    $faq->syncTerms('faq.category', [$fees->id, $general->id, $urgent->id]);   // tag ignored, one category kept

    expect($faq->termIdsIn('faq.category'))->toBe([$fees->id])
        ->and($faq->termIdsIn('shared.tags'))->toBe([$urgent->id])            // other vocabulary untouched
        ->and(Faq::query()->withTerm($fees)->pluck('id')->all())->toBe([$faq->id]);
});

it('takes a deleted term off its content and brings it back on restore (BRL-TAX-01)', function () {
    $parent = term('faq.category', 'Admission');
    $child = term('faq.category', 'Undergraduate', $parent);
    $faq = Faq::factory()->create();
    $faq->syncTerms('faq.category', [$child->id]);

    expect(fn () => $parent->delete())->toThrow(ParentHasActiveChildren::class);

    expect($child->usageCount())->toBe(1);
    $child->delete();
    expect($faq->termIdsIn('faq.category'))->toBe([]);

    $child->restore();
    expect($faq->termIdsIn('faq.category'))->toBe([$child->id]);

    $child->forceDelete();
    expect(DB::table('core_termables')->count())->toBe(0);
});

it('is for Manage:Taxonomy holders, listing vocabularies of active modules only', function () {
    $this->actingAs(makeUser())->get(VocabularyResource::getUrl('index'))->assertForbidden();
    $this->actingAs(makeUser(['Manage:Taxonomy']))->get(VocabularyResource::getUrl('index'))->assertOk();

    Livewire::test(ListVocabularies::class)
        ->assertCanSeeTableRecords(Vocabulary::whereIn('key', ['faq.category', 'shared.tags'])->get());

    app(ModuleManager::class)->syncBundled();
    app(ModuleManager::class)->deactivate('faq');

    Livewire::test(ListVocabularies::class)
        ->assertCanNotSeeTableRecords(Vocabulary::where('key', 'faq.category')->get())
        ->assertCanSeeTableRecords(Vocabulary::where('key', 'shared.tags')->get());

    expect(config('filament-shield.custom_permissions'))->toContain('Manage:Taxonomy');
});

it('manages terms on the vocabulary page (FR-TAX-02)', function () {
    $this->actingAs(makeUser(['Manage:Taxonomy']));
    $vocabulary = vocab()->find('faq.category');
    $parent = term('faq.category', 'Admission');

    $this->get(VocabularyResource::getUrl('edit', ['record' => $vocabulary]))->assertOk();

    Livewire::test(TermsRelationManager::class, ['ownerRecord' => $vocabulary, 'pageClass' => EditVocabulary::class])
        ->callTableAction('create', data: ['name' => ['en' => 'Scholarship', 'bn' => 'বৃত্তি'], 'parent_id' => $parent->id, 'is_active' => true])
        ->assertHasNoTableActionErrors();

    $child = Term::whereJsonContains('name->en', 'Scholarship')->first();
    expect($child->parent_id)->toBe($parent->id)
        ->and($child->vocabulary_id)->toBe($vocabulary->id)
        ->and($child->getTranslation('name', 'bn'))->toBe('বৃত্তি');

    Livewire::test(TermsRelationManager::class, ['ownerRecord' => $vocabulary, 'pageClass' => EditVocabulary::class])
        ->assertCanSeeTableRecords([$parent, $child])
        ->callTableAction('delete', $parent);                                    // has an active child: refused

    expect($parent->refresh()->trashed())->toBeFalse();
});

it('saves category and new tags from the FAQ form (FR-TAX-04)', function () {
    app(ModulePermissions::class)->sync('Faq');
    $this->actingAs(makeUser(['ViewAny:Faq', 'Create:Faq', 'Update:Faq', 'View:Faq']));
    $general = term('faq.category', 'General');

    Livewire::test(CreateFaq::class)
        ->fillForm(['question' => 'How to apply?', 'answer' => 'Online.', 'category' => $general->id])
        ->call('create')
        ->assertHasNoFormErrors();

    $faq = Faq::where('question', 'How to apply?')->first();
    expect($faq->termIdsIn('faq.category'))->toBe([$general->id]);

    Livewire::test(EditFaq::class, ['record' => $faq->getRouteKey()])
        ->assertFormSet(['category' => $general->id]);
});

it('drops a module\'s vocabularies and links when it is uninstalled with its data', function () {
    $general = term('faq.category', 'General');
    $tag = term('shared.tags', 'urgent');
    $faq = Faq::factory()->create();
    $faq->syncTerms('faq.category', [$general->id]);
    $faq->syncTerms('shared.tags', [$tag->id]);

    event(new ModuleUninstalling(Manifest::fromDirectory(base_path('Modules/Faq')), deleteData: true));

    expect(Vocabulary::where('key', 'faq.category')->exists())->toBeFalse()
        ->and(Term::withTrashed()->whereKey($general->id)->exists())->toBeFalse()
        ->and(Term::whereKey($tag->id)->exists())->toBeTrue()                     // shared tag stays
        ->and(DB::table('core_termables')->count())->toBe(0);
});
