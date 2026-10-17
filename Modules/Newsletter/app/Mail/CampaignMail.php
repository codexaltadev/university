<?php

namespace Modules\Newsletter\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Modules\Newsletter\Models\Campaign;
use Modules\Newsletter\Models\Subscriber;

/** One campaign email (FR-NLT-05): the body, a one-click unsubscribe link at the bottom and in the List-Unsubscribe header (FR-NLT-03). */
class CampaignMail extends Mailable
{
    public function __construct(public Campaign $campaign, public ?Subscriber $subscriber = null, public bool $test = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->test ? '[TEST] ' : '').$this->campaign->subject);
    }

    public function headers(): Headers
    {
        return new Headers(text: $this->unsubscribeUrl() ? ['List-Unsubscribe' => '<'.$this->unsubscribeUrl().'>'] : []);
    }

    public function content(): Content
    {
        return new Content(view: 'newsletter::mail.campaign', with: [
            'campaign' => $this->campaign,
            'unsubscribeUrl' => $this->unsubscribeUrl(),
            'footer' => settings()->get('newsletter.footer_note', null, $this->campaign->locale),
        ]);
    }

    protected function unsubscribeUrl(): ?string
    {
        return $this->subscriber ? route('newsletter.unsubscribe', ['subscriber' => $this->subscriber->token]) : null;
    }
}
