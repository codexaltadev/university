<?php

namespace Modules\Newsletter\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Modules\Newsletter\Models\Subscriber;

/** The double opt-in email (FR-NLT-02): a signed confirm link, and the unsubscribe link (FR-NLT-03). */
class ConfirmNewsletter extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Subscriber $subscriber) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('newsletter::newsletter.confirm.subject'))
            ->line(__('newsletter::newsletter.confirm.line'))
            ->action(__('newsletter::newsletter.confirm.button'), URL::temporarySignedRoute('newsletter.confirm', now()->addDays(7), ['subscriber' => $this->subscriber->token]))
            ->line(__('newsletter::newsletter.confirm.ignore'))
            ->line(__('newsletter::newsletter.unsubscribe_line', ['url' => route('newsletter.unsubscribe', ['subscriber' => $this->subscriber->token])]));
    }
}
