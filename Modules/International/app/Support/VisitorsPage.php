<?php

namespace Modules\International\Support;

use Illuminate\Support\Str;
use Modules\International\Models\Visitor;

/**
 * What the visitors page shows, ready for a theme (`$page` in international::visitors): a card per
 * guest — the start of their words, photo, name, title, country, date — that opens to all of it.
 * Themes hold no PHP logic (T-1).
 */
class VisitorsPage
{
    public const EXCERPT = 200;

    public function __construct(protected array $data) {}

    public function listingUrl(): string
    {
        return url(($this->data['base'] ?? '').'/'.Visitor::PATH);
    }

    /**
     * @return list<array{id: string, excerpt: string, body: ?string, name: string, title: ?string, country: ?string, flag: ?string, date: ?string, photo: ?string, album: ?string}>
     */
    public function cards(): array
    {
        return collect(($this->data['visitors'] ?? null)?->items() ?? [])->map(function (Visitor $v): array {
            $text = trim(html_entity_decode(strip_tags((string) $v->body)));
            $album = $v->album();

            return [
                'id' => 'visitor-'.$v->getKey(),
                'excerpt' => Str::limit($text, self::EXCERPT),
                'body' => filled($v->body) ? Str::sanitizeHtml((string) $v->body) : null,
                'name' => (string) $v->name,
                'title' => collect([$v->designation, $v->organization])->filter()->implode(', ') ?: null,
                'country' => $v->country?->name,
                'flag' => $v->country?->emoji(),
                'date' => $v->visited_on?->translatedFormat('j F Y'),
                'photo' => $v->firstMedia('photo')?->url('medium'),
                'album' => $album && method_exists($album, 'url') ? $album->url() : null,
            ];
        })->all();
    }
}
