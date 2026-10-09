<?php

/*
| Phase 2.6b — Main Site Queue (FR-CNT-H08/H09) and Highlights view (FR-CNT-H06).
*/

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\ContentRelation\Filament\Pages\Highlights;
use Modules\ContentRelation\Filament\Pages\MainSiteQueue;
use Modules\Faq\Models\Faq;
use Platform\Modules\ModulePermissions;
use Platform\Relations\ContentRelation;
use Platform\Relations\MainSiteQueue as Queue;
use Platform\Relations\MainSiteRequest;
use Platform\Relations\RelationEngine;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Faq');
    app(ModulePermissions::class)->syncAttachable('faq', [Faq::class]);

    $this->cse = department(faculty(), 'CSE');
    $this->cseKey = $this->cse->getMorphClass().':'.$this->cse->id;
    $this->engine = app(RelationEngine::class);
});

function faqIn(array $targets, bool $mainSite = false, string $question = 'Q'): Faq
{
    $faq = Faq::factory()->create(['question' => $question]);
    app(RelationEngine::class)->sync($faq, $targets, mainSite: $mainSite, user: null);

    return $faq;
}

function queueKey(Faq $faq): string
{
    return $faq->getMorphClass().':'.$faq->id;
}

it('lists entity content not on the main site, requests first, for PublishMain holders', function () {
    $plain = faqIn([$this->cseKey], question: 'Lab hours');
    faqIn([$this->cseKey], mainSite: true, question: 'Already on main');
    $requested = faqIn([$this->cseKey], question: 'Exam rules');
    $old = faqIn([$this->cseKey], question: 'Old one');
    ContentRelation::query()->where('content_id', $old->id)->update(['created_at' => now()->subDays(40)]);

    $entityUser = makeUser(['ViewAny:Faq'], [$this->cse]);
    $this->engine->requestMainSite($requested, $entityUser);

    $this->actingAs($entityUser)->get(MainSiteQueue::getUrl())->assertForbidden();

    $admin = makeUser(['ViewAny:Faq', 'PublishMain:Faq']);
    $this->actingAs($admin)->get(MainSiteQueue::getUrl())->assertOk()->assertSee('Exam rules');

    expect(app(Queue::class)->items($admin, ['days' => 30])->pluck('title')->all())->toBe(['Exam rules', 'Lab hours'])
        ->and(app(Queue::class)->items($admin)->pluck('title')->all())->toContain('Old one')       // no period: everything
        ->and(app(Queue::class)->items($admin)->first()['requested_by'])->toBe($entityUser->name)
        ->and(MainSiteQueue::getNavigationBadge())->toBe('1');

    Livewire::test(MainSiteQueue::class)
        ->assertCanSeeTableRecords([queueKey($requested), queueKey($plain)])
        ->assertCanNotSeeTableRecords([queueKey($old)]);                                          // default: last 30 days
});

it('publishes or dismisses from the queue, one or many', function () {
    $a = faqIn([$this->cseKey], question: 'A');
    $b = faqIn([$this->cseKey], question: 'B');
    $c = faqIn([$this->cseKey], question: 'C');
    $this->engine->requestMainSite($a, makeUser([], [$this->cse]));
    $this->actingAs(makeUser(['ViewAny:Faq', 'PublishMain:Faq']));

    Livewire::test(MainSiteQueue::class)
        ->callTableAction('publish', queueKey($a))
        ->callTableAction('dismiss', queueKey($b))
        ->assertCanNotSeeTableRecords([queueKey($a), queueKey($b)])
        ->assertCanSeeTableRecords([queueKey($c)]);

    expect($a->isOnMainSite())->toBeTrue()
        ->and(MainSiteRequest::where('content_id', $a->id)->value('decision'))->toBe(MainSiteRequest::PUBLISHED)
        ->and($b->isOnMainSite())->toBeFalse()
        ->and($b->targetKeys())->toBe([$this->cseKey]);                                              // still shown in CSE

    Livewire::test(MainSiteQueue::class)
        ->callTableBulkAction('publishSelected', [queueKey($c)]);
    expect($c->isOnMainSite())->toBeTrue();
});

it('shows, reorders and removes highlights of a place (FR-CNT-H06)', function () {
    $site = RelationEngine::SITE_KEY;
    [$first, $second, $third] = collect(['First', 'Second', 'Third'])->map(fn ($q, $i) => tap(
        faqIn([$this->cseKey], mainSite: true, question: $q),
        fn ($faq) => $this->engine->sync($faq, [$this->cseKey], mainSite: true, highlights: [$site => ['order' => $i + 1]], user: null),
    ))->all();
    $expired = faqIn([$this->cseKey], mainSite: true, question: 'Expired');
    $this->engine->sync($expired, [$this->cseKey], mainSite: true, highlights: [$site => ['order' => 9, 'until' => now()->subDay()]], user: null);
    $this->engine->sync($second, [$this->cseKey], mainSite: true, highlights: [$this->cseKey => ['order' => 1]], user: null);

    $this->actingAs(makeUser(['ViewAny:Faq']))->get(Highlights::getUrl())->assertForbidden();   // no HighlightMain
    $this->actingAs(makeUser(['ViewAny:Faq', 'HighlightMain:Faq']));
    $relation = fn (Faq $faq, string $place = 'site:0') => ContentRelation::where('content_id', $faq->id)->get()->first(fn ($r) => $r->targetKey() === $place);

    $page = Livewire::test(Highlights::class)
        ->assertSeeInOrder(['First', 'Second', 'Third'])
        ->assertDontSee('Expired');                                                                // past its date

    $page->call('reorderTable', [$relation($third)->id, $relation($first)->id, $relation($second)->id]);
    expect([$relation($third)->highlight_order, $relation($first)->highlight_order, $relation($second)->highlight_order])->toBe([1, 2, 3]);

    $page->callTableAction('unhighlight', $relation($first));
    expect($relation($first)->is_highlighted)->toBeFalse()
        ->and($first->isOnMainSite())->toBeTrue();                                                  // still shown

    // Another place
    Livewire::test(Highlights::class)
        ->fillForm(['context' => $this->cseKey])
        ->assertSet('context', $this->cseKey)
        ->assertCanSeeTableRecords([$relation($second, $this->cseKey)])
        ->assertCanNotSeeTableRecords([$relation($third)]);
});
