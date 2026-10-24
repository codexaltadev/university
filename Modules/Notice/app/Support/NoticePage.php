<?php

namespace Modules\Notice\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Notice\Models\Notice;

/**
 * What a notice's page shows, ready for a theme (`$page` in notice::show): its date, views,
 * category, text made safe and its files with their titles (the first one is DIU's
 * "View/Download"). Themes hold no PHP logic (T-1).
 */
class NoticePage
{
    public function __construct(public Notice $notice, protected Collection $attachments, protected string $base = '', protected ?string $locale = null) {}

    public function boardUrl(): string
    {
        return url($this->base.'/'.Notice::PATH);
    }

    public function date(): string
    {
        return (string) $this->notice->active_at?->translatedFormat('F j, Y');
    }

    public function views(): int
    {
        return $this->notice->viewTotal();
    }

    public function category(): ?string
    {
        return $this->notice->termsIn(Notice::CATEGORY)->first()?->name;
    }

    public function body(): ?string
    {
        return filled($this->notice->body) ? Str::sanitizeHtml((string) $this->notice->body) : null;
    }

    /** @return list<array{title: string, url: string, pdf: bool, ext: string}> */
    public function files(): array
    {
        $locale = $this->locale ?? app()->getLocale();

        return $this->attachments->map(function ($usage) use ($locale): array {
            $titles = (array) ($usage->meta['title'] ?? []);
            $name = (string) $usage->item->fileName();

            return [
                'title' => (string) (($titles[$locale] ?? null) ?: (collect($titles)->filter()->first() ?: ($usage->item->title ?: $name))),
                'url' => (string) $usage->item->url(),
                'pdf' => str_ends_with(strtolower($name), '.pdf'),
                'ext' => strtoupper(pathinfo($name, PATHINFO_EXTENSION)),
            ];
        })->values()->all();
    }
}
