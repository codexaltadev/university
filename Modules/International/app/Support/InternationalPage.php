<?php

namespace Modules\International\Support;

use Illuminate\Support\Str;
use Modules\International\Models\Conference;
use Modules\International\Models\Country;
use Modules\International\Models\Institution;

/**
 * What the international pages show, ready for a theme (`$page` in international::home, ::partners,
 * ::conferences and ::conference), in DIU's look: /international — the numbers and the featured
 * partners (DIU's /featured-partner cards: logo, country flag, name, line); /international/partners
 * as DIU's /international-linkage — "Browse by Section" links and Quick Links, a country to pick with
 * its universities, a card per university (logo, name, country, Visit Website); the conferences as
 * picture cards. Titles, lines, pictures and links from Settings › International. Themes hold no PHP logic (T-1).
 */
class InternationalPage
{
    public function __construct(protected array $data) {}

    public function text(string $key): ?string
    {
        return filled($value = settings('international.'.$key)) ? (string) $value : null;
    }

    public function image(string $page): ?string
    {
        return media_url(settings("international.{$page}_image"), 'large');
    }

    public function url(string $path = '', array $query = []): string
    {
        return url(($this->data['base'] ?? '').'/'.trim(InternationalWebsite::ROOT.'/'.$path, '/')).($query ? '?'.http_build_query($query) : '');
    }

    /** @return list<array{label: string, url: string}> */
    public function breadcrumbs(?string $title = null, ?string $path = null, ?string $last = null): array
    {
        return array_values(array_filter([
            ['label' => __('theme::theme.home'), 'url' => url(($this->data['base'] ?? '') ?: '/')],
            ['label' => __('international::international.title'), 'url' => $this->url()],
            $title !== null ? ['label' => $title, 'url' => $this->url((string) $path)] : null,
            $last !== null ? ['label' => $last, 'url' => request()->url()] : null,
        ]));
    }

    /** @return list<array{id: string, label: string, url: string, current: bool, arrow: bool}> DIU's "Browse by Section" */
    public function sideLinks(): array
    {
        return collect(link_list(settings('international.side_links')))->map(fn (array $l, int $i): array => [
            'id' => 'side-'.$i, 'label' => $l['label'], 'url' => $l['url'], 'current' => false, 'arrow' => true,
        ])->all();
    }

    /** @return list<array{label: string, url: string}> */
    public function quickLinks(): array
    {
        return link_list(settings('international.quick_links'));
    }

    /** The country's flag picture (Media Library), else null — the emoji is the text fallback (Windows shows no flag emoji) */
    protected function flagImage(?Country $country): ?string
    {
        return $country?->firstMedia('flag')?->url('thumb') ?? $country?->firstMedia('flag')?->url();
    }

    // /international -------------------------------------------------------------------------

    /** @return list<array{value: int, label: string, url: string, icon: string}> */
    public function stats(): array
    {
        $counts = $this->data['counts'] ?? [];

        return array_values(array_filter([
            ['value' => (int) ($counts['partners'] ?? 0), 'label' => __('international::international.partners'), 'url' => $this->url('partners'), 'icon' => 'building'],
            ['value' => (int) ($counts['countries'] ?? 0), 'label' => __('international::international.countries'), 'url' => $this->url('partners', ['view' => 'countries']), 'icon' => 'globe'],
            ['value' => (int) ($counts['conferences'] ?? 0), 'label' => __('international::international.conferences'), 'url' => $this->url('conferences'), 'icon' => 'users'],
            ['value' => (int) ($counts['visitors'] ?? 0), 'label' => __('international::international.visitors'), 'url' => url(($this->data['base'] ?? '').'/visitors'), 'icon' => 'user'],
        ], fn (array $s): bool => $s['value'] > 0));
    }

    /** @return list<array<string, mixed>> the highlighted partners (DIU's featured partners) */
    public function featured(): array
    {
        return collect($this->data['featured'] ?? [])->map(fn (Institution $i): array => $this->university($i))->values()->all();
    }

    // /international/partners ----------------------------------------------------------------

    /** @return list<array{value: string, label: string, flag: string, count: int, current: bool}> */
    public function countries(): array
    {
        $current = $this->data['country'] ?? null;
        $counts = $this->data['countryCounts'] ?? collect();

        return collect($this->data['countries'] ?? [])->map(fn (Country $c): array => [
            'value' => (string) $c->iso2,
            'label' => (string) $c->name,
            'flag' => (string) $c->emoji(),
            'flag_img' => $this->flagImage($c),
            'count' => (int) ($counts[$c->id] ?? 0),
            'current' => $current !== null && $current->is($c),
        ])->values()->all();
    }

    /** @return array{label: string, flag: string, total: int}|null the chosen country and its universities */
    public function chosen(): ?array
    {
        $country = $this->data['country'] ?? null;

        return $country ? ['label' => (string) $country->name, 'flag' => (string) $country->emoji(), 'flag_img' => $this->flagImage($country), 'total' => (int) ($this->data['partners'] ?? null)?->total()] : null;
    }

    /** @return list<array<string, mixed>> */
    public function universities(): array
    {
        return collect(($this->data['partners'] ?? null)?->items() ?? [])->map(fn (Institution $i): array => $this->university($i))->values()->all();
    }

    /** @return array{name: string, logo: ?string, url: ?string, country: ?string, flag: string, text: ?string, agreement: ?string, expired: bool} */
    public function university(Institution $i): array
    {
        return [
            'name' => (string) $i->name,
            'logo' => $i->firstMedia('logo')?->url('medium') ?? $i->firstMedia('logo')?->url(),
            'url' => $i->website_url ?: null,
            'country' => $i->country?->name ? (string) $i->country->name : null,
            'flag' => (string) $i->country?->emoji(),
            'flag_img' => $this->flagImage($i->country),
            'text' => filled($i->description) ? Str::limit(strip_tags((string) $i->description), 220) : null,
            'agreement' => $i->agreement_type && $i->agreement_type !== 'other' ? __("international::international.agreements.{$i->agreement_type}") : null,
            'expired' => method_exists($i, 'isExpired') && $i->isExpired(),
        ];
    }

    // /international/conferences ---------------------------------------------------------------

    /** @return list<array{title: string, url: string, image: ?string, when: ?string, venue: ?string, category: ?string}> */
    public function conferences(string $which): array
    {
        return collect($this->data[$which] ?? [])->map(fn (Conference $c): array => $this->conferenceCard($c))->values()->all();
    }

    public function conferenceCard(Conference $c): array
    {
        return [
            'title' => (string) $c->title,
            'url' => url(($this->data['base'] ?? '').'/'.Conference::PATH.'/'.$c->slug()),
            'image' => $c->firstMedia('image')?->url('large') ?? $c->firstMedia('image')?->url(),
            'when' => $c->starts_on ? ($c->ends_on && ! $c->ends_on->isSameDay($c->starts_on) ? $c->starts_on->translatedFormat('j M').' – '.$c->ends_on->translatedFormat('j M Y') : $c->starts_on->translatedFormat('j M Y')) : null,
            'venue' => filled($c->venue) ? (string) $c->venue : null,
            'category' => $c->termsIn(Conference::CATEGORY)->first()?->name,
        ];
    }

    public function conference(): array
    {
        /** @var Conference $c */
        $c = $this->data['conference'];

        return $this->conferenceCard($c) + [
            'body' => filled($c->body) ? Str::sanitizeHtml((string) $c->body) : null,
            'website' => $c->website_url ?: null,
            'organizer' => filled($c->organizer_label) ? (string) $c->organizer_label : null,
        ];
    }
}
