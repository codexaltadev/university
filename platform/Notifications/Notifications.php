<?php

namespace Platform\Notifications;

use Closure;
use Platform\Registry\Registry;

/**
 * Sends admin notifications (C-18). The Notification core module provides the
 * sender (channels, preferences, templates); without it nothing is sent, so a
 * module may always call send().
 *
 *   app(Notifications::class)->send(new InquiryReceived([...], $inquiry));
 */
class Notifications
{
    protected ?Closure $sender = null;

    public function __construct(protected Registry $registry) {}

    /** @param Closure(NotificationType): int $sender returns how many users were notified */
    public function useSender(Closure $sender): void
    {
        $this->sender = $sender;
    }

    public function send(NotificationType $notification): int
    {
        return $this->sender ? ($this->sender)($notification) : 0;
    }

    /** @return array<string, class-string<NotificationType>> key => type, of active modules */
    public function types(): array
    {
        return collect($this->registry->all('notification'))
            ->pluck('class')
            ->filter(fn (string $class): bool => is_subclass_of($class, NotificationType::class))
            ->mapWithKeys(fn (string $class): array => [$class::key() => $class])
            ->all();
    }
}
