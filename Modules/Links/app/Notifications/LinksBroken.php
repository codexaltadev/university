<?php

namespace Modules\Links\Notifications;

use Illuminate\Support\Collection;
use Modules\Links\Filament\Resources\Links\LinkResource;
use Platform\Notifications\NotificationType;
use Platform\Notifications\Recipients;

/** FR-LINK-03: a link stopped working — the people who may edit it (its places), in the bell and by email. */
class LinksBroken extends NotificationType
{
    public static function key(): string
    {
        return 'links.broken';
    }

    public static function label(): string
    {
        return __('links::links.notice.label');
    }

    public static function placeholders(): array
    {
        return ['title' => 'The link\'s title', 'url' => 'Its address', 'status' => 'The answer (0 = none)'];
    }

    public static function defaultSubject(string $locale): string
    {
        return __('links::links.notice.subject', [], $locale);
    }

    public static function defaultBody(string $locale): string
    {
        return __('links::links.notice.body', [], $locale);
    }

    public function recipients(): Collection
    {
        return Recipients::permitted('Update:Link', $this->subject);
    }

    public function url(): ?string
    {
        return $this->subject ? LinkResource::getUrl('edit', ['record' => $this->subject]) : null;
    }
}
