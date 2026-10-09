<?php

/*
| Phase 2.6 — content form components on the FAQ form: PublishingPanel,
| RelatedToField ("Show in" + main site / request), HighlightPanel
| (FR-GEN-10/50/51, FR-CNT-C01, FR-CNT-H01/H02/H07/H09, FR-REL-05, AC-REL-01).
*/

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Faq\Filament\Resources\Faqs\Pages\CreateFaq;
use Modules\Faq\Filament\Resources\Faqs\Pages\EditFaq;
use Modules\Faq\Models\Faq;
use Modules\Office\Models\Office;
use Platform\Filament\Components\HighlightPanel;
use Platform\Modules\ModuleManager;
use Platform\Modules\ModulePermissions;
use Platform\Relations\RelationEngine;
use Platform\Relations\Targets;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Faq');
    app(ModulePermissions::class)->syncAttachable('faq', [Faq::class]);   // PublishMain:Faq, HighlightMain:Faq

    $this->engineering = faculty();
    $this->cse = department($this->engineering, 'CSE');
    $this->eee = department($this->engineering, 'EEE');
});

function keyOfEntity($model): string
{
    return $model->getMorphClass().':'.$model->getKey();
}

const FAQ_EDITOR = ['ViewAny:Faq', 'View:Faq', 'Create:Faq', 'Update:Faq'];

it('lets an Admin show content in entities and on the main site, highlighted (AC-REL-01, FR-CNT-H07)', function () {
    $this->actingAs(makeUser([...FAQ_EDITOR, 'PublishMain:Faq', 'HighlightMain:Faq']));
    $site = RelationEngine::SITE_KEY;

    Livewire::test(CreateFaq::class)
        ->assertFormSet(['main_site' => true, 'is_active' => true])                 // on by default for those who decide
        ->assertFormFieldIsVisible('published_at')                                   // FAQ has a publish date …
        ->assertFormFieldIsHidden('expires_at')                                      // … but no expiry
        ->fillForm([
            'question' => 'How do I apply?', 'answer' => 'Online.',
            'show_in' => [keyOfEntity($this->engineering), keyOfEntity($this->cse)],
            'main_site' => true,
        ])
        ->fillForm(['highlights' => [HighlightPanel::id($site) => ['on' => true, 'order' => 2, 'until' => now()->addMonth()->toDateString()]]])
        ->call('create')
        ->assertHasNoFormErrors();

    $faq = Faq::where('question', 'How do I apply?')->first();
    expect($faq->targetKeys())->toEqualCanonicalizing([keyOfEntity($this->engineering), keyOfEntity($this->cse)])
        ->and($faq->isOnMainSite())->toBeTrue()
        ->and(app(RelationEngine::class)->highlightsOf($faq)[$site]['order'])->toBe(2);

    // The edit form shows what is saved
    Livewire::test(EditFaq::class, ['record' => $faq->getRouteKey()])
        ->assertFormSet([
            'show_in' => $faq->targetKeys(),
            'main_site' => true,
            'highlights.'.HighlightPanel::id($site).'.on' => true,
            'highlights.'.HighlightPanel::id($site).'.order' => 2,
        ])
        ->fillForm(['main_site' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($faq->refresh()->isOnMainSite())->toBeFalse();
});

it('keeps entity users inside their scope, their entity preselected, no main-site switch (FR-GEN-51)', function () {
    $user = makeUser(FAQ_EDITOR, [$this->cse]);
    $this->actingAs($user);

    expect(collect(app(Targets::class)->options($user))->flatMap(fn ($group) => array_keys($group))->all())
        ->toBe([keyOfEntity($this->cse)]);

    Livewire::test(CreateFaq::class)
        ->assertFormSet(['show_in' => [keyOfEntity($this->cse)]])
        ->assertFormFieldIsHidden('main_site')
        ->assertFormFieldIsVisible('request_main_site')
        ->fillForm(['question' => 'Lab hours?', 'answer' => '9-5', 'show_in' => [keyOfEntity($this->eee)]])
        ->call('create')
        ->assertHasFormErrors(['show_in']);                                          // outside the scope

    Livewire::test(CreateFaq::class)
        ->fillForm(['question' => 'Lab hours?', 'answer' => '9-5', 'show_in' => []])
        ->call('create')
        ->assertHasFormErrors(['show_in']);                                          // at least one

    expect(Faq::count())->toBe(0);

    Livewire::test(CreateFaq::class)
        ->fillForm(['question' => 'Lab hours?', 'answer' => '9-5', 'request_main_site' => true])
        ->fillForm(['highlights' => [HighlightPanel::id(keyOfEntity($this->cse)) => ['on' => true, 'order' => 1]]])
        ->call('create')
        ->assertHasNoFormErrors();

    $faq = Faq::first();
    $engine = app(RelationEngine::class);
    expect($faq->targetKeys())->toBe([keyOfEntity($this->cse)])
        ->and($faq->isOnMainSite())->toBeFalse()
        ->and($engine->hasPendingRequest($faq))->toBeTrue()                          // FR-CNT-H09
        ->and(array_keys($engine->highlightsOf($faq)))->toBe([keyOfEntity($this->cse)]);

    // Changing their mind withdraws the request
    Livewire::test(EditFaq::class, ['record' => $faq->getRouteKey()])
        ->assertFormSet(['request_main_site' => true])
        ->fillForm(['request_main_site' => false])
        ->call('save');
    expect($engine->hasPendingRequest($faq))->toBeFalse();
});

it('offers only targets of active modules (FR-REL-05)', function () {
    $office = Office::create(['name' => 'Registrar Office']);
    $admin = superAdmin();

    expect(collect(app(Targets::class)->options($admin))->flatMap(fn ($group) => array_keys($group))->all())
        ->toContain(keyOfEntity($office));

    app(ModuleManager::class)->syncBundled();
    app(ModuleManager::class)->deactivate('office');

    expect(collect(app(Targets::class)->options($admin))->flatMap(fn ($group) => array_keys($group))->all())
        ->not->toContain(keyOfEntity($office));
});

it('cannot highlight on the main site without HighlightMain (FR-CNT-H02)', function () {
    $this->actingAs(makeUser([...FAQ_EDITOR, 'PublishMain:Faq']));
    $site = RelationEngine::SITE_KEY;

    Livewire::test(CreateFaq::class)
        ->fillForm(['question' => 'Fees?', 'answer' => 'See the fees page.', 'show_in' => [keyOfEntity($this->cse)], 'main_site' => true])
        ->fillForm(['highlights' => [HighlightPanel::id($site) => ['on' => true, 'order' => 1]]])
        ->call('create')
        ->assertHasNoFormErrors();

    $faq = Faq::first();
    expect($faq->isOnMainSite())->toBeTrue()
        ->and(app(RelationEngine::class)->highlightsOf($faq))->not->toHaveKey($site);
});
