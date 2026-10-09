<?php

namespace Modules\Notification\Types;

use Illuminate\Support\Collection;
use Modules\Notification\Models\ReminderRule;
use Platform\Notifications\NotificationType;
use Platform\Notifications\Recipients;

/**
 * FR-NOTI-12: "Your department's notices have not been updated for 95 days" to the
 * entity's responsible users; the rule's own message per language, and in
 * Each-record mode the old records with their edit links (AC-NOTI-03).
 */
class ContentReminderNotice extends NotificationType
{
    public static function key(): string
    {
        return 'core.content_reminder';
    }

    public static function label(): string
    {
        return __('notification::reminder.types.content_reminder');
    }

    public static function placeholders(): array
    {
        return [
            'entity' => 'Department / office name', 'module' => 'Content (Notices …)', 'days' => 'Days since the last update',
            'message' => 'The rule\'s message', 'records' => 'Old records with edit links',
        ];
    }

    public static function defaultSubject(string $locale): string
    {
        return __('notification::reminder.texts.subject', [], $locale);
    }

    public static function defaultBody(string $locale): string
    {
        return __('notification::reminder.texts.body', [], $locale);
    }

    public function recipients(): Collection
    {
        return $this->subject ? Recipients::responsibleFor($this->subject, $this->data['permission']) : collect();
    }

    public function values(string $locale): array
    {
        /** @var ReminderRule|null $rule */
        $rule = $this->data['rule'] ?? null;
        $field = ($this->data['status'] ?? 'stale') === 'empty' ? 'empty_message' : 'stale_message';
        $message = $rule?->getTranslation($field, $locale, true)
            ?: __("notification::reminder.texts.default_{$field}", ['module' => $this->data['module'] ?? '', 'days' => $this->data['days'] ?? ''], $locale);

        return [
            'entity' => (string) ($this->data['entity'] ?? ''),
            'module' => (string) ($this->data['module'] ?? ''),
            'days' => (string) ($this->data['days'] ?? ''),
            'message' => strtr((string) $message, ['{module}' => $this->data['module'] ?? '', '{days}' => $this->data['days'] ?? '', '{entity}' => $this->data['entity'] ?? '']),
            'records' => (string) ($this->data['records'] ?? ''),
        ];
    }
}
