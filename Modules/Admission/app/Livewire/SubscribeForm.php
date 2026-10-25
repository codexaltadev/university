<?php

namespace Modules\Admission\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Admission\Models\AdmissionSubscriber;
use Modules\Admission\Notifications\ConfirmSubscription;

/**
 * "Get admission updates" (FR-ADM-09): email or WhatsApp. An email gets a confirmation
 * link (and an unsubscribe link); the same contact twice is one subscriber. Limited per
 * visitor (5 a minute) with a hidden trap field. View: modules/admission/livewire/subscribe-form.
 */
class SubscribeForm extends Component
{
    public string $channel = 'email';

    public string $contact = '';

    /** Honeypot */
    public string $website = '';

    #[Locked]
    public string $topic = 'admission';

    #[Locked]
    public string $source = 'admission';

    public ?string $done = null;

    public function mount(string $topic = 'admission', string $source = 'admission'): void
    {
        $this->topic = mb_substr($topic, 0, 50);
        $this->source = mb_substr($source, 0, 30);
    }

    public function subscribe(): void
    {
        $this->validate([
            'channel' => ['required', 'in:'.implode(',', AdmissionSubscriber::CHANNELS)],
            'contact' => $this->channel === 'email' ? ['required', 'email', 'max:191'] : ['required', 'regex:/^\+?[0-9 ()-]{8,20}$/'],
        ], [], ['contact' => __('admission::admission.fields.contact')]);

        $key = 'admission-subscribe:'.request()->ip();
        if ($this->website !== '' || RateLimiter::tooManyAttempts($key, 5)) {
            $this->done = __('admission::admission.subscribe.thanks');   // say nothing more to a bot

            return;
        }
        RateLimiter::hit($key, 60);

        $contact = AdmissionSubscriber::normalizeContact($this->channel, $this->contact);
        $subscriber = AdmissionSubscriber::query()->firstOrNew(['channel' => $this->channel, 'contact' => $contact, 'topic' => $this->topic]);
        $subscriber->fill(['source' => $subscriber->source ?? $this->source, 'unsubscribed_at' => null, 'unsubscribe_reason' => null, 'is_active' => true])->save();

        if ($this->channel === 'email' && $subscriber->verified_at === null) {
            Notification::route('mail', $contact)->notify(new ConfirmSubscription($subscriber));
        }

        $this->reset('contact');
        $this->done = __($this->channel === 'email' ? 'admission::admission.subscribe.check_email' : 'admission::admission.subscribe.thanks');
    }

    public function render(): View
    {
        return view('admission::livewire.subscribe-form', [
            'channels' => AdmissionSubscriber::CHANNELS,
            'whatsappGroup' => filled($url = settings('admission.whatsapp_group_url')) ? (string) $url : null,
        ]);
    }
}
