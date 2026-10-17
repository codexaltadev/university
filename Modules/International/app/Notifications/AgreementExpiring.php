<?php

namespace Modules\International\Notifications;

use Illuminate\Support\Collection;
use Modules\International\Filament\Resources\Institutions\InstitutionResource;
use Platform\Notifications\NotificationType;
use Platform\Notifications\Recipients;

/** FR-INTL-03: an agreement with a partner institution ends soon — the people who may edit it. */
class AgreementExpiring extends NotificationType
{
    public static function key(): string
    {
        return 'international.agreement-expiring';
    }

    public static function label(): string
    {
        return __('international::international.notice.label');
    }

    public static function placeholders(): array
    {
        return ['name' => 'The partner institution', 'country' => 'Its country', 'date' => 'The day it ends', 'days' => 'Days left'];
    }

    public static function defaultSubject(string $locale): string
    {
        return __('international::international.notice.subject', [], $locale);
    }

    public static function defaultBody(string $locale): string
    {
        return __('international::international.notice.body', [], $locale);
    }

    public function recipients(): Collection
    {
        return Recipients::permitted('Update:Institution', $this->subject);
    }

    public function url(): ?string
    {
        return $this->subject ? InstitutionResource::getUrl('edit', ['record' => $this->subject]) : null;
    }
}
