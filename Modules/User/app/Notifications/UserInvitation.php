<?php

namespace Modules\User\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Invitation to the admin panel: the new user sets their own password (FR-USER-07).
 */
class UserInvitation extends Notification
{
    use Queueable;

    public function __construct(public readonly string $url) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('user::user.invitation.subject', ['app' => config('app.name')]))
            ->greeting(__('user::user.invitation.greeting', ['name' => $notifiable->name]))
            ->line(__('user::user.invitation.line', ['app' => config('app.name')]))
            ->action(__('user::user.invitation.action'), $this->url)
            ->line(__('user::user.invitation.expiry', ['hours' => intdiv((int) config('auth.passwords.users.expire'), 60)]));
    }
}
