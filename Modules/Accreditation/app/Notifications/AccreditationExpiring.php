<?php

namespace Modules\Accreditation\Notifications;

use Illuminate\Support\Collection;
use Modules\Accreditation\Filament\Resources\Accreditations\AccreditationResource;
use Platform\Notifications\NotificationType;
use Platform\Notifications\Recipients;

/** FR-ACR-02: an accreditation ends soon — the people who may edit it, in the bell and by email. */
class AccreditationExpiring extends NotificationType
{
    public static function key(): string
    {
        return 'accreditation.expiring';
    }

    public static function label(): string
    {
        return __('accreditation::accreditation.notice.label');
    }

    public static function placeholders(): array
    {
        return ['title' => 'The accreditation', 'agency' => 'Its agency', 'date' => 'The day it ends', 'days' => 'Days left'];
    }

    public static function defaultSubject(string $locale): string
    {
        return __('accreditation::accreditation.notice.subject', [], $locale);
    }

    public static function defaultBody(string $locale): string
    {
        return __('accreditation::accreditation.notice.body', [], $locale);
    }

    public function recipients(): Collection
    {
        return Recipients::permitted('Update:Accreditation', $this->subject);
    }

    public function url(): ?string
    {
        return $this->subject ? AccreditationResource::getUrl('edit', ['record' => $this->subject]) : null;
    }
}
