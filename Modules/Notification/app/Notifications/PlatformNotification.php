<?php

namespace Modules\Notification\Notifications;

use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * One rendered notification for one user (FR-NOTI-01/02): the bell (Filament
 * database notification) and / or email, on the queue. Text is already in the
 * user's language and wording.
 */
class PlatformNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param list<string> $channels */
    public function __construct(
        public string $key,
        public string $subject,
        public string $body,
        public ?string $url,
        public array $channels,
        public ?string $icon = null,
    ) {
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $message = FilamentNotification::make()
            ->title($this->subject)
            ->body(nl2br(e($this->body)))
            ->icon($this->icon ?? 'heroicon-o-bell');

        if ($this->url) {
            $message->actions([Action::make('view')->label(__('notification::notification.view'))->url($this->url)->markAsRead()]);
        }

        return [...$message->getDatabaseMessage(), 'key' => $this->key];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->subject)->greeting(__('notification::notification.greeting', ['name' => $notifiable->name ?? '']));

        foreach (preg_split('/\R{2,}/', trim($this->body)) as $paragraph) {
            $mail->line($paragraph);
        }

        return $this->url ? $mail->action(__('notification::notification.view'), $this->url) : $mail;
    }
}
