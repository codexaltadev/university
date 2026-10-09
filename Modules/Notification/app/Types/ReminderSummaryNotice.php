<?php

namespace Modules\Notification\Types;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Platform\Notifications\NotificationType;
use Platform\Notifications\Recipients;

/** FR-NOTI-13: one weekly summary of every empty / stale entity for the admins. */
class ReminderSummaryNotice extends NotificationType
{
    public static function key(): string
    {
        return 'core.reminder_summary';
    }

    public static function label(): string
    {
        return __('notification::reminder.types.summary');
    }

    public static function defaultChannels(): array
    {
        return ['mail'];
    }

    public static function placeholders(): array
    {
        return ['count' => 'How many need an update', 'lines' => 'One line per entity and content'];
    }

    public static function defaultSubject(string $locale): string
    {
        return __('notification::reminder.texts.summary_subject', [], $locale);
    }

    public static function defaultBody(string $locale): string
    {
        return __('notification::reminder.texts.summary_body', [], $locale);
    }

    public function recipients(): Collection
    {
        return Recipients::permitted('ViewAny:ReminderRule');
    }

    public function url(): ?string
    {
        return Route::has('filament.admin.pages.content-freshness') ? route('filament.admin.pages.content-freshness') : null;
    }

    public function values(string $locale): array
    {
        $lines = (array) ($this->data['lines'] ?? []);

        return ['count' => (string) count($lines), 'lines' => implode("\n", array_map(fn (string $l): string => '• '.$l, $lines))];
    }
}
