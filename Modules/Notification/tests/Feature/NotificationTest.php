<?php

/*
| Phase 2.11a — Notification (C-18): types from modules, recipients by permission
| and scope, bell and email on the queue, preferences, templates with placeholders,
| core events, clean-up. FR-NOTI-01..08.
*/

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification as NotificationFake;
use Livewire\Livewire;
use Modules\Faq\Models\Faq;
use Modules\Notification\Filament\Pages\MyNotifications;
use Modules\Notification\Filament\Pages\NotificationTemplates;
use Modules\Notification\Models\Preference;
use Modules\Notification\Models\Template;
use Modules\Notification\Notifications\PlatformNotification;
use Modules\Notification\Support\Notifier;
use Modules\Notification\Types\UserCreatedNotice;
use Platform\Notifications\Notifications;
use Platform\Notifications\NotificationType;
use Platform\Notifications\Recipients;
use Platform\Registry\Registry;
use Platform\Relations\RelationEngine;
use Spatie\Permission\Models\Permission;

/** A module's own type: "new FAQ question", for users who see that FAQ (scope). */
class FaqAskedNotice extends NotificationType
{
    public static function key(): string
    {
        return 'faq.asked';
    }

    public static function placeholders(): array
    {
        return ['question' => 'The question'];
    }

    public static function defaultSubject(string $locale): string
    {
        return $locale === 'bn' ? 'নতুন প্রশ্ন: {question}' : 'New question: {question}';
    }

    public static function defaultBody(string $locale): string
    {
        return 'Someone asked "{question}".';
    }

    public function recipients(): Collection
    {
        return Recipients::permitted('ViewAny:Faq', $this->subject);
    }

    public function url(): ?string
    {
        return '/admin/faqs';
    }
}

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Permission::findOrCreate('ViewAny:Faq', 'web');
    app(Registry::class)->add('notification', 'faq', ['class' => FaqAskedNotice::class]);

    $this->engineering = faculty();
    $this->cse = department($this->engineering, 'CSE');
    $this->eee = department($this->engineering, 'EEE');
    $this->faq = Faq::factory()->create(['question' => 'When does admission open?']);
    app(RelationEngine::class)->sync($this->faq, [$this->cse->getMorphClass().':'.$this->cse->id], user: null);   // shown in CSE
});

it('sends a module\'s notification to permitted users in scope, plus Super Admins (FR-NOTI-03/07)', function () {
    NotificationFake::fake();
    $cseUser = makeUser(['ViewAny:Faq'], [$this->cse]);
    $eeeUser = makeUser(['ViewAny:Faq'], [$this->eee]);
    $noPermission = makeUser([], [$this->cse]);
    $admin = superAdmin();

    $sent = app(Notifications::class)->send(new FaqAskedNotice(['question' => 'When does admission open?'], $this->faq));

    expect($sent)->toBe(2);
    NotificationFake::assertSentTo([$cseUser, $admin], PlatformNotification::class,
        fn (PlatformNotification $n, array $channels): bool => $channels === ['database', 'mail'] && $n->subject === 'New question: When does admission open?');
    NotificationFake::assertNotSentTo([$eeeUser, $noPermission], PlatformNotification::class);
});

it('puts it in the bell with a link and sends the email on the queue (FR-NOTI-01/02)', function () {
    $user = makeUser(['ViewAny:Faq']);
    app(Notifications::class)->send(new FaqAskedNotice(['question' => 'Fees?'], $this->faq));

    $stored = DatabaseNotification::query()->where('notifiable_id', $user->id)->sole();
    expect($stored->data['title'])->toBe('New question: Fees?')
        ->and($stored->data['format'])->toBe('filament')
        ->and(json_encode($stored->data['actions']))->toContain('\/admin\/faqs')
        ->and($user->unreadNotifications()->count())->toBe(1);

    expect(new PlatformNotification('x', 's', 'b', null, ['mail']))->toBeInstanceOf(ShouldQueue::class);
});

it('writes in each user\'s language and the admin\'s template (FR-NOTI-06)', function () {
    $notifier = app(Notifier::class);
    $notice = new FaqAskedNotice(['question' => 'ফি কত?'], $this->faq);

    expect($notifier->render($notice, 'bn'))->toBe(['নতুন প্রশ্ন: ফি কত?', 'Someone asked "ফি কত?".']);

    Template::create(['key' => 'faq.asked', 'subject' => ['en' => 'Question received: {question}'], 'body' => ['en' => 'Reply soon: {question}'], 'is_active' => true]);
    expect($notifier->render($notice, 'en'))->toBe(['Question received: ফি কত?', 'Reply soon: ফি কত?'])
        ->and($notifier->render($notice, 'bn')[0])->toBe('Question received: ফি কত?');   // no Bangla wording → default language

    Template::query()->update(['is_active' => false]);
    expect($notifier->render($notice, 'en')[0])->toBe('New question: ফি কত?');
});

it('lets each user choose bell and / or email per type (FR-NOTI-05)', function () {
    NotificationFake::fake();
    $user = makeUser(['ViewAny:Faq']);
    $this->actingAs($user);

    Livewire::test(MyNotifications::class)
        ->assertSchemaStateSet(['faq__asked' => ['database', 'mail']])
        ->fillForm(['faq__asked' => ['database'], 'core__user_created' => []])
        ->call('save')
        ->assertNotified();

    app(Notifications::class)->send(new FaqAskedNotice(['question' => 'Q'], $this->faq));
    NotificationFake::assertSentTo($user, PlatformNotification::class, fn ($n, array $channels): bool => $channels === ['database']);

    // Back to the default removes the row
    app(Notifier::class)->setChannels($user, 'faq.asked', ['database', 'mail']);
    expect(Preference::query()->where('user_id', $user->id)->pluck('type')->all())->toBe(['core.user_created']);
});

it('edits templates per type for permitted admins only', function () {
    $this->actingAs(makeUser());
    $this->get(NotificationTemplates::getUrl())->assertForbidden();

    $this->actingAs(superAdmin());
    Livewire::withQueryParams(['type' => 'faq.asked'])->test(NotificationTemplates::class)
        ->assertSchemaStateSet(['subject.en' => 'New question: {question}'])
        ->assertSee('{question} — The question')
        ->fillForm(['subject' => ['en' => 'Asked: {question}', 'bn' => 'প্রশ্ন: {question}'], 'body' => ['en' => 'Body {question}']])
        ->call('save')
        ->assertNotified();

    expect(Template::query()->where('key', 'faq.asked')->sole()->getTranslation('subject', 'bn'))->toBe('প্রশ্ন: {question}');

    Livewire::withQueryParams(['type' => 'faq.asked'])->test(NotificationTemplates::class)->call('useDefault');
    expect(Template::query()->count())->toBe(0);
});

it('tells user managers about a new admin user, not the creator (FR-NOTI-04)', function () {
    Permission::findOrCreate('ViewAny:User', 'web');
    NotificationFake::fake();
    $creator = superAdmin();
    $manager = makeUser(['ViewAny:User']);
    $this->actingAs($creator);

    $new = User::factory()->create(['name' => 'Rahim']);

    NotificationFake::assertSentTo($manager, PlatformNotification::class,
        fn (PlatformNotification $n): bool => $n->key === UserCreatedNotice::key() && str_contains($n->body, 'Rahim') && str_contains($n->subject, 'Rahim'));
    NotificationFake::assertNotSentTo([$creator, $new], PlatformNotification::class);
});

it('removes read notifications after 90 days (FR-NOTI-08)', function () {
    $user = makeUser(['ViewAny:Faq']);
    app(Notifications::class)->send(new FaqAskedNotice(['question' => 'Old'], $this->faq));
    app(Notifications::class)->send(new FaqAskedNotice(['question' => 'New'], $this->faq));

    [$old, $new] = $user->notifications()->oldest()->get()->all();
    $old->forceFill(['read_at' => now()->subDays(91)])->save();
    $new->forceFill(['read_at' => now()->subDays(10)])->save();

    expect(Notifier::clean())->toBeGreaterThanOrEqual(1)
        ->and($user->notifications()->count())->toBe(1);
});
