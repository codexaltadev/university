<?php

namespace Modules\Accreditation\Support;

use Illuminate\Support\Str;
use Modules\Accreditation\Models\Accreditation;
use Modules\Accreditation\Models\Ranking;

/**
 * What the recognition pages show, ready for a theme (`$page` in accreditation::rankings, ::index and
 * ::show): /rankings as DIU's — every result as a row (logo, title linking to the ranking, year, its
 * text) and the Quick Links; /accreditations as cards tagged with their category (Accredited,
 * Membership …) beside each other. Texts, picture and links from Settings › Accreditation. Themes hold no PHP logic (T-1).
 */
class RecognitionPage
{
    public function __construct(protected array $data) {}

    public function text(string $key): ?string
    {
        return filled($value = settings('accreditation.'.$key)) ? (string) $value : null;
    }

    public function image(): ?string
    {
        return media_url(settings('accreditation.image'), 'large');
    }

    public function url(string $path): string
    {
        return url(($this->data['base'] ?? '').'/'.$path);
    }

    /** @return list<array{label: string, url: string}> */
    public function breadcrumbs(string $title, string $path, ?string $last = null): array
    {
        return array_values(array_filter([
            ['label' => __('theme::theme.home'), 'url' => url(($this->data['base'] ?? '') ?: '/')],
            ['label' => $title, 'url' => $this->url($path)],
            $last !== null ? ['label' => $last, 'url' => request()->url()] : null,
        ]));
    }

    /** @return list<array{label: string, url: string}> */
    public function quickLinks(): array
    {
        return link_list(settings('accreditation.quick_links'));
    }

    /** @return list<array{key: string, title: string, url: ?string, logo: ?string, year: int, rank: ?string, text: ?string}> every result, newest first */
    public function rankings(): array
    {
        return collect($this->data['all'] ?? $this->data['latest'] ?? [])->map(fn (Ranking $r): array => [
            'key' => (string) $r->agency_key,
            'title' => $r->label(),
            'url' => $r->ranking_url ?: null,
            'logo' => $r->firstMedia('logo')?->url('medium') ?? $r->firstMedia('logo')?->url(),
            'year' => (int) $r->year,
            'rank' => filled($r->rank_label) ? (string) $r->rank_label : ($r->rank_value ? '#'.$r->rank_value : null),
            'text' => filled($r->body) ? Str::sanitizeHtml((string) $r->body) : (filled($r->summary) ? e((string) $r->summary) : null),
        ])->values()->all();
    }

    /** @return list<array{title: string, url: string, logo: ?string, agency: ?string, tag: ?string, accredited: bool, since: ?string, until: ?string, expired: bool, text: ?string}> */
    public function accreditations(): array
    {
        return collect($this->data['groups'] ?? [])->flatMap(fn (array $g) => $g['items']->map(fn (Accreditation $a): array => $this->card($a, $g['term']?->name)))->values()->all();
    }

    /** @return list<array{label: string, count: int}> the categories present, for the tags' legend */
    public function categories(): array
    {
        return collect($this->data['groups'] ?? [])->filter(fn (array $g): bool => $g['term'] !== null)
            ->map(fn (array $g): array => ['label' => (string) $g['term']->name, 'count' => $g['items']->count()])->values()->all();
    }

    public function card(Accreditation $a, ?string $category = null): array
    {
        $category ??= $a->termsIn(Accreditation::CATEGORY)->first()?->name;

        return [
            'title' => (string) $a->title,
            'url' => url(($this->data['base'] ?? '').'/'.Accreditation::PATH.'/'.($a->slug() ?? $a->getKey())),
            'website' => $a->website_url ?: null,
            'logo' => $a->firstMedia('logo')?->url('medium') ?? $a->firstMedia('logo')?->url(),
            'agency' => filled($a->agency) ? (string) $a->agency : null,
            'tag' => $category ? (string) $category : null,
            'accredited' => $category !== null && ! Str::contains(Str::lower($category), 'member'),
            'since' => $a->awarded_on?->translatedFormat('M Y'),
            'until' => $a->expires_on?->translatedFormat('M Y'),
            'expired' => $a->isExpired(),
            'text' => filled($a->body) ? Str::sanitizeHtml((string) $a->body) : null,
        ];
    }

    public function accreditation(): array
    {
        return $this->card($this->data['accreditation']);
    }
}
