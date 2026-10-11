<?php

namespace Modules\Admission\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Modules\Admission\Models\AdmissionSubscriber;

/** The email asking a new admission subscriber to confirm (FR-ADM-09), with an unsubscribe link. */
class ConfirmSubscription extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public AdmissionSubscriber $subscriber) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('admission::admission.subscribe.mail_subject'))
            ->line(__('admission::admission.subscribe.mail_line'))
            ->action(__('admission::admission.subscribe.confirm'), URL::signedRoute('admission.subscriber.verify', $this->subscriber))
            ->line(__('admission::admission.subscribe.mail_unsubscribe', ['url' => URL::signedRoute('admission.subscriber.unsubscribe', $this->subscriber)]));
    }
}
