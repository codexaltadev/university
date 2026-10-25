<?php

namespace Modules\Achievements\Support;

use Illuminate\Support\Str;
use Modules\Achievements\Models\Achievement;

/**
 * What the achievements pages show, ready for a theme (`$page` in achievements::index and
 * achievements::show), in DIU's card look (its events and Honour Board): cards with the picture,
 * level, date and title; filters; an achievement's details beside its story. Themes hold no PHP
 * logic (T-1).
 */
class AchievementsPage
{
    public function __construct(protected array $data) {}

    public function listingUrl(): string
    {
        return url(($this->data['base'] ?? '').'/'.Achievement::PATH);
    }

    /** The listing's address with some filters changed (null = removed); the page starts over */
    public function url(array $change = []): string
    {
        $query = array_filter(array_merge([
            'level' => $this->data['level'] ?? null,
            'year' => $this->data['year'] ?? null,
            'category' => ($this->data['category'] ?? null)?->slug(),
        ], $change), fn ($v): bool => $v !== null && $v !== '');

        return $this->listingUrl().($query ? '?'.http_build_query($query) : '');
    }

    /** @return list<array{label: string, url: string, current: bool}> "All" and the levels */
    public function levels(): array
    {
        $level = $this->data['level'] ?? null;

        return [
            ['label' => __('achievements::achievements.all'), 'url' => $this->url(['level' => null]), 'current' => $level === null],
            ...array_map(fn (string $l): array => ['label' => __("achievements::achievements.levels.{$l}"), 'url' => $this->url(['level' => $l]), 'current' => $level === $l], Achievement::LEVELS),
        ];
    }

    /** @return list<array{title: string, url: string, image: ?string, level: string, position: ?string, date: string, text: ?string}> */
    public function cards(): array
    {
        $base = $this->data['base'] ?? '';

        return collect(($this->data['achievements'] ?? null)?->items() ?? [])->map(fn (Achievement $a): array => [
            'title' => (string) $a->title,
            'url' => url($base !== '' ? $base.'/'.Achievement::PATH.'/'.$a->slug() : $a->url()),
            'image' => $a->firstMedia('image')?->url('medium'),
            'level' => __("achievements::achievements.levels.{$a->level}"),
            'position' => filled($a->position_label) ? (string) $a->position_label : null,
            'date' => $a->achieved_on->translatedFormat('j F Y'),
            'text' => filled($a->summary) ? Str::limit((string) $a->summary, 150) : (filled($a->competition) ? (string) $a->competition : null),
        ])->all();
    }

    public function body(): ?string
    {
        $a = $this->data['achievement'] ?? null;

        return $a && filled($a->body) ? Str::sanitizeHtml((string) $a->body) : null;
    }

    /** @return list<array{label: string, value: string, icon: string}> */
    public function details(): array
    {
        $a = $this->data['achievement'] ?? null;
        if (! $a instanceof Achievement) {
            return [];
        }

        return array_values(array_filter([
            ['label' => __('achievements::achievements.fields.level'), 'value' => __("achievements::achievements.levels.{$a->level}"), 'icon' => 'tag'],
            filled($a->position_label) ? ['label' => __('achievements::achievements.fields.position_label'), 'value' => (string) $a->position_label, 'icon' => 'graduation-cap'] : null,
            ['label' => __('achievements::achievements.fields.achieved_on'), 'value' => $a->achieved_on->translatedFormat('j F Y'), 'icon' => 'calendar'],
            filled($a->competition) ? ['label' => __('achievements::achievements.fields.competition'), 'value' => (string) $a->competition, 'icon' => 'clipboard'] : null,
            filled($a->issuer) ? ['label' => __('achievements::achievements.fields.issuer'), 'value' => (string) $a->issuer, 'icon' => 'building'] : null,
            filled($a->team_name) ? ['label' => __('achievements::achievements.fields.team_name'), 'value' => (string) $a->team_name, 'icon' => 'users'] : null,
        ]));
    }
}
