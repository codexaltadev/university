<?php

/*
| Phase 2.11b — Content Update Reminder (BRD 7.10): rules, freshness sources,
| daily check, reminders to responsible users with interval, each-record mode,
| weekly summary, report, widget. FR-NOTI-09..16, BRL-NOTI-02/03, AC-NOTI-01..03.
*/

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification as NotificationFake;
use Livewire\Livewire;
use Modules\Faq\Models\Faq;
use Modules\Notification\Filament\Pages\ContentFreshness;
use Modules\Notification\Filament\Resources\ReminderRules\Pages\CreateReminderRule;
use Modules\Notification\Filament\Widgets\MyContentFreshness;
use Modules\Notification\Models\ReminderRule;
use Modules\Notification\Models\ReminderState;
use Modules\Notification\Notifications\PlatformNotification;
use Modules\Notification\Support\Reminders;
use Platform\Modules\ModuleManager;
use Platform\Modules\ModulePermissions;
use Platform\Relations\RelationEngine;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Notification');
    Permission::findOrCreate('Update:Faq', 'web');

    $this->engineering = faculty();
    $this->cse = department($this->engineering, 'CSE');
    $this->eee = department($this->engineering, 'EEE');
    $this->type = $this->cse->getMorphClass();
    $this->reminders = app(Reminders::class);

    $this->faqIn = function ($entity, int $daysOld, string $question = 'Q'): Faq {
        $faq = Faq::factory()->create(['question' => $question]);
        app(RelationEngine::class)->sync($faq, [$entity->getMorphClass().':'.$entity->id], user: null);
        Faq::query()->whereKey($faq->id)->update(['updated_at' => now()->subDays($daysOld)]);

        return $faq->refresh();
    };

    $this->rule = ReminderRule::create([
        'module_slug' => 'faq', 'entity_type' => $this->type, 'max_days_without_update' => 90, 'remind_every_days' => 7,
        'is_mandatory' => true, 'check_mode' => 'latest', 'stale_message' => ['en' => '{entity}: FAQs are {days} days old.'],
    ]);

    $this->cseUser = makeUser(['Update:Faq'], [$this->cse]);
    $this->eeeUser = makeUser(['Update:Faq'], [$this->eee]);
});

function stateOf(ReminderRule $rule, $entity): ?ReminderState
{
    return ReminderState::query()->where('rule_id', $rule->id)->where('entity_id', $entity->id)->first();
}

it('reminds the entity\'s users when content is stale, again after the interval, until it is updated (AC-NOTI-01, FR-NOTI-11/12/14)', function () {
    NotificationFake::fake();
    $faq = ($this->faqIn)($this->cse, 95);
    ($this->faqIn)($this->eee, 10);

    $this->reminders->check();

    expect(stateOf($this->rule, $this->cse)->status)->toBe('stale')
        ->and(stateOf($this->rule, $this->cse)->days())->toBe(95)
        ->and(stateOf($this->rule, $this->eee)->status)->toBe('ok');
    NotificationFake::assertSentTo($this->cseUser, PlatformNotification::class,
        fn (PlatformNotification $n): bool => $n->key === 'core.content_reminder' && str_contains($n->body, 'CSE: FAQs are 95 days old.'));
    NotificationFake::assertNotSentTo($this->eeeUser, PlatformNotification::class);

    // Not again before 7 days …
    $this->travel(3)->days();
    $this->reminders->check();
    NotificationFake::assertSentToTimes($this->cseUser, PlatformNotification::class, 1);

    // … then again
    $this->travel(5)->days();
    $this->reminders->check();
    NotificationFake::assertSentToTimes($this->cseUser, PlatformNotification::class, 2);
    expect(stateOf($this->rule, $this->cse)->reminder_count)->toBe(2);

    // Updated → OK, reminders stop
    $faq->touch();
    $this->reminders->check();
    expect(stateOf($this->rule, $this->cse)->status)->toBe('ok')
        ->and(stateOf($this->rule, $this->cse)->reminder_count)->toBe(0);
});

it('reports an entity without content as empty when the rule is mandatory (AC-NOTI-02)', function () {
    NotificationFake::fake();
    ($this->faqIn)($this->cse, 1);

    $this->reminders->check();
    expect(stateOf($this->rule, $this->eee)->status)->toBe('empty');
    NotificationFake::assertSentTo($this->eeeUser, PlatformNotification::class, fn ($n): bool => str_contains($n->body, 'There is no FAQ yet'));

    $this->rule->update(['is_mandatory' => false]);
    $this->reminders->check();
    expect(stateOf($this->rule, $this->eee)->status)->toBe('ok');
});

it('lists only the old records with edit links in each-record mode (AC-NOTI-03)', function () {
    NotificationFake::fake();
    $this->rule->update(['check_mode' => 'each_record', 'stale_message' => null]);
    ($this->faqIn)($this->cse, 5, 'Fresh one');
    $old = ($this->faqIn)($this->cse, 100, 'Hostel fees');
    ($this->faqIn)($this->cse, 20, 'Another fresh one');

    $this->reminders->check();

    expect(stateOf($this->rule, $this->cse)->stale_records)->toBe([$old->id]);
    NotificationFake::assertSentTo($this->cseUser, PlatformNotification::class,
        fn (PlatformNotification $n): bool => str_contains($n->body, '• Hostel fees') && ! str_contains($n->body, 'Fresh one') && str_contains($n->body, '/admin/faqs/'.$old->id.'/edit'));
});

it('skips inactive rules, inactive modules and inactive entities (BRL-NOTI-02)', function () {
    NotificationFake::fake();
    ($this->faqIn)($this->cse, 200);

    $this->rule->update(['is_active' => false]);
    expect($this->reminders->check()['checked'])->toBe(0);

    $this->rule->update(['is_active' => true]);
    $this->cse->update(['is_active' => false]);
    $this->reminders->check();
    expect(stateOf($this->rule, $this->cse))->toBeNull();

    $this->cse->update(['is_active' => true]);
    app(ModuleManager::class)->syncBundled();
    app(ModuleManager::class)->deactivate('faq');
    expect($this->reminders->check()['checked'])->toBe(0);
    NotificationFake::assertNotSentTo($this->cseUser, PlatformNotification::class);   // EEE (empty) was reminded meanwhile
});

it('sends admins one weekly summary, marking entities without a responsible user (FR-NOTI-13, BRL-NOTI-03)', function () {
    NotificationFake::fake();
    $admin = superAdmin();
    $this->eeeUser->scopes()->delete();   // EEE has nobody
    ($this->faqIn)($this->cse, 120);

    $this->reminders->check();
    expect($this->reminders->summary())->toBe(1);

    NotificationFake::assertSentTo($admin, PlatformNotification::class, function (PlatformNotification $n, array $channels): bool {
        return $n->key === 'core.reminder_summary' && $channels === ['mail']
            && str_contains($n->body, 'CSE — FAQ: not updated (120 days)')
            && str_contains($n->body, 'EEE — FAQ: no content — no responsible user');
    });
});

it('creates rules on screen, one per content and entity type (FR-NOTI-09)', function () {
    $this->actingAs(superAdmin());

    Livewire::test(CreateReminderRule::class)
        ->fillForm(['module_slug' => 'page', 'entity_type' => $this->type, 'max_days_without_update' => 30, 'remind_every_days' => 3, 'check_mode' => 'latest'])
        ->call('create')
        ->assertHasNoFormErrors();

    Livewire::test(CreateReminderRule::class)
        ->fillForm(['module_slug' => 'faq', 'entity_type' => $this->type, 'max_days_without_update' => 30, 'remind_every_days' => 3, 'check_mode' => 'latest'])
        ->call('create')
        ->assertHasFormErrors(['entity_type']);   // FAQ × department already exists

    expect(array_keys($this->reminders->sources()))->toContain('faq', 'page');
});

it('shows the freshness report and the entity user\'s dashboard widget (FR-NOTI-15/16)', function () {
    ($this->faqIn)($this->cse, 95);
    $this->reminders->check();

    $this->actingAs(superAdmin());
    $this->get(ContentFreshness::getUrl(['type' => $this->type]))->assertOk()
        ->assertSee('CSE')->assertSee('95 days')->assertSee('no content');

    $this->actingAs($this->cseUser);
    expect(MyContentFreshness::canView())->toBeTrue()
        ->and(MyContentFreshness::lines())->toBe(['CSE: FAQ not updated for 95 days.']);
    $this->get(ContentFreshness::getUrl())->assertForbidden();

    $this->actingAs(makeUser(['Update:Faq'], [$this->engineering]));
    expect(MyContentFreshness::canView())->toBeFalse();
});
