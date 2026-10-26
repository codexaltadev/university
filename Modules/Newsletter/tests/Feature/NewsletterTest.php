<?php

/*
| Phase 3.29 — M-39 Newsletter (DB-06 v1.3 §10, SRS Services FR-NLT-01..06, 08, BRL-NLT-01/02):
| sign-up with double opt-in, one-click unsubscribe with a reason, suppression on import, the
| throttled campaign with its report, and Admission's subscribers on "Admission Updates".
*/

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Modules\Admission\Models\AdmissionSubscriber;
use Modules\Newsletter\Jobs\SendCampaignBatch;
use Modules\Newsletter\Livewire\SubscribeForm;
use Modules\Newsletter\Mail\CampaignMail;
use Modules\Newsletter\Models\Campaign;
use Modules\Newsletter\Models\MailingList;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Notifications\ConfirmNewsletter;
use Modules\Newsletter\Support\CampaignSender;
use Modules\Newsletter\Support\SubscriberImporter;
use Modules\Newsletter\Support\Subscriptions;
use Platform\Modules\ModulePermissions;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Newsletter');
});

it('signs up with double opt-in and unsubscribes in one click (FR-NLT-01/02/03, BRL-NLT-01)', function () {
    Notification::fake();
    $general = app(Subscriptions::class)->general();
    $notices = MailingList::factory()->create(['key' => 'notices', 'name' => ['en' => 'Notices']]);

    Livewire::test(SubscribeForm::class, ['source' => 'page'])->set('email', 'Student@Example.com')->set('lists', [$notices->id])->call('subscribe')
        ->assertSet('done', __('newsletter::newsletter.form.check_email'));
    Livewire::test(SubscribeForm::class)->set('email', 'student@example.com')->set('lists', [$general->id])->call('subscribe');

    $subscriber = Subscriber::query()->sole();
    expect($subscriber->email)->toBe('student@example.com')->and($subscriber->status)->toBe('pending')
        ->and($subscriber->lists->pluck('key')->sort()->values()->all())->toBe(['general', 'notices']);
    Notification::assertSentOnDemand(ConfirmNewsletter::class);

    $this->get(route('newsletter.confirm', ['subscriber' => $subscriber->token]))->assertForbidden();            // unsigned
    $this->get(URL::temporarySignedRoute('newsletter.confirm', now()->addDay(), ['subscriber' => $subscriber->token]))->assertOk()->assertSee(__('newsletter::newsletter.confirm.done'));
    expect($subscriber->fresh()->status)->toBe('subscribed');

    $this->get(route('newsletter.unsubscribe', ['subscriber' => $subscriber->token]))->assertOk()->assertSee(__('newsletter::newsletter.unsubscribe.done'));
    $this->post(route('newsletter.unsubscribe.reason', ['subscriber' => $subscriber->token]), ['reason' => 'Too many emails'])->assertOk();
    expect($subscriber->fresh())->status->toBe('unsubscribed')->unsubscribe_reason->toBe('Too many emails');
    $this->get('/newsletter')->assertOk()->assertSee('wire:', false);
});

it('keeps unsubscribed people out on import and sends a campaign in throttled batches with a report (FR-NLT-04/05/06, BRL-NLT-02)', function () {
    Mail::fake();
    Queue::fake();
    settings()->save('newsletter', ['per_minute' => 2]);
    $list = app(Subscriptions::class)->general();
    $gone = Subscriber::factory()->create(['email' => 'gone@example.com', 'status' => 'unsubscribed']);
    $file = tempnam(sys_get_temp_dir(), 'nl').'.csv';
    file_put_contents($file, "Email,Name\na@example.com,A\nb@example.com,B\nGONE@example.com,Gone\nc@example.com,\nnot-an-email,X\na@example.com,A again\n");
    $checked = app(SubscriberImporter::class)->check($file);
    @unlink($file);
    expect($checked['rows'])->toHaveCount(4)->and($checked['errors'])->toHaveCount(1);
    expect(app(SubscriberImporter::class)->save($checked['rows'], [$list->id]))->toBe(['added' => 3, 'kept' => 1]);
    expect($gone->fresh()->status)->toBe('unsubscribed');
    Subscriber::factory()->create(['email' => 'pending@example.com', 'status' => 'pending'])->join([$list->id]);

    $campaign = Campaign::factory()->create(['subject' => 'Spring news']);
    $campaign->lists()->sync([$list->id]);
    expect($campaign->audience()->pluck('email')->sort()->values()->all())->toBe(['a@example.com', 'b@example.com', 'c@example.com']);

    $sender = app(CampaignSender::class);
    expect($sender->start($campaign))->toBe(3);
    Queue::assertPushed(SendCampaignBatch::class);
    expect($sender->batch($campaign->fresh()))->toBeTrue();                                             // 2 a minute: one left
    Mail::assertSent(CampaignMail::class, 2);
    expect($sender->batch($campaign->fresh()))->toBeFalse();
    expect($campaign->fresh())->send_status->toBe('sent')->sent_count->toBe(3)->failed_count->toBe(0);
    expect($sender->start($campaign->fresh()))->toBe(0);                                                // never twice

    $html = (new CampaignMail($campaign, Subscriber::query()->where('email', 'a@example.com')->first()))->render();
    expect($html)->toContain('newsletter/unsubscribe/');
    $sender->test($campaign, 'editor@example.com');
    Mail::assertSent(CampaignMail::class, fn (CampaignMail $m) => $m->test && $m->hasTo('editor@example.com'));
});

it('puts Admission\'s confirmed email subscribers on "Admission Updates" (FR-NLT-08)', function () {
    Notification::fake();
    $admission = AdmissionSubscriber::query()->create(['channel' => 'email', 'contact' => 'applicant@example.com', 'topic' => 'admission', 'source' => 'admission', 'is_active' => true]);
    expect(Subscriber::query()->count())->toBe(0);                                                      // not confirmed yet

    $admission->update(['verified_at' => now()]);
    $subscriber = Subscriber::query()->where('email', 'applicant@example.com')->sole();
    expect($subscriber->status)->toBe('subscribed')->and($subscriber->lists->pluck('key')->all())->toBe([MailingList::ADMISSION]);

    $admission->update(['unsubscribed_at' => now()]);
    expect($subscriber->fresh()->lists->first()->pivot->unsubscribed_at)->not->toBeNull();
});

it('shows the past issues on /newsletter: sent, in its language, to public lists only (Phase 4.7g)', function () {
    $public = MailingList::query()->create(['key' => 'general', 'name' => ['en' => 'General'], 'is_public' => true, 'is_active' => true]);
    $staff = MailingList::query()->create(['key' => 'staff', 'name' => ['en' => 'Staff'], 'is_public' => false, 'is_active' => true]);
    $issue = function (string $subject, MailingList $list, string $status = 'sent', string $locale = 'en'): Campaign {
        $c = new Campaign(['subject' => $subject, 'locale' => $locale, 'body' => '<h2>Admission</h2><p>Apply until 22 December.</p>', 'is_active' => true]);
        $c->forceFill(['send_status' => $status, 'sent_at' => $status === 'sent' ? now()->subDay() : null])->save();
        $c->lists()->sync([$list->id]);

        return $c;
    };
    $october = $issue('October issue', $public);
    $issue('Staff memo', $staff);                                // to a private list
    $issue('Draft issue', $public, 'draft');                    // not sent
    $issue('বাংলা সংখ্যা', $public, 'sent', 'bn');                // another language

    $this->get('/newsletter')->assertOk()
        ->assertSeeInOrder(['DIU Newsletter', 'Subscribe', 'Past issues', 'October issue', 'Admission Apply until 22 December.', 'Read'])
        ->assertDontSee('Staff memo')->assertDontSee('Draft issue')->assertDontSee('বাংলা সংখ্যা');
    $this->get('/newsletter/issues/'.$october->id)->assertOk()->assertSee('October issue')->assertSee('<h2>Admission</h2>', false);
    $this->get('/newsletter/issues/'.Campaign::query()->where('subject', 'Staff memo')->value('id'))->assertNotFound();

    settings()->save('newsletter', ['show_issues' => false]);
    $this->get('/newsletter')->assertOk()->assertDontSee('Past issues')->assertDontSee('October issue');
});
