<?php

namespace Platform\Notifications;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * One kind of admin notification (C-18, FR-NOTI-03): "new inquiry", "module
 * installed", "content reminder" … A module registers its types with
 * $r->notification(InquiryReceived::class) and sends them with
 * app(Notifications::class)->send(new InquiryReceived(['name' => …], $inquiry)).
 *
 * Text comes from the admin's template for the type (FR-NOTI-06), else from
 * defaultSubject() / defaultBody(); {placeholders} are filled from values().
 * Each user chooses in-app and / or email per type (FR-NOTI-05).
 */
abstract class NotificationType
{
    /** @param array<string, mixed> $data placeholder values and anything the type needs */
    public function __construct(public array $data = [], public ?Model $subject = null) {}

    /** "inquiry.received" — stored on preferences and templates; never change it. */
    abstract public static function key(): string;

    /** Who receives it (FR-NOTI-07), usually Recipients::permitted('ViewAny:Inquiry', $this->subject). */
    abstract public function recipients(): Collection;

    public static function label(): string
    {
        return Str::headline(str_replace('.', ' ', static::key()));
    }

    /** @return list<string> "database" (bell) and / or "mail" */
    public static function defaultChannels(): array
    {
        return ['database', 'mail'];
    }

    /** @return array<string, string> placeholder => what it holds, shown on the template screen */
    public static function placeholders(): array
    {
        return [];
    }

    abstract public static function defaultSubject(string $locale): string;

    abstract public static function defaultBody(string $locale): string;

    /** Where "View" goes. */
    public function url(): ?string
    {
        return null;
    }

    /** @return array<string, string> placeholder => value (plain text) in the recipient's language */
    public function values(string $locale): array
    {
        return collect($this->data)->filter(fn ($v): bool => is_scalar($v) || $v === null)->map(fn ($v): string => (string) $v)->all();
    }
}
