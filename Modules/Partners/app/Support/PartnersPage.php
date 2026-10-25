<?php

namespace Modules\Partners\Support;

use Illuminate\Support\Str;
use Modules\Partners\Models\Partner;

/**
 * What the partner pages show, ready for a theme (`$page` in partners::index and partners::discounts):
 * /partners as DIU's /mps/{category} — the types as tabs, a logo card per organization with its
 * website's domain; /student-discounts as DIU's /discount-partners/{category} — the categories with
 * their partners on the left (one chosen, ?category=), the Quick Links, a blue head with the number
 * and a search (?q=), a row per partner (name, discount, details to open, website). Titles, lines and
 * links from Settings › Partners. Themes hold no PHP logic (T-1).
 */
class PartnersPage
{
    public function __construct(protected array $data) {}

    public function text(string $key): ?string
    {
        return filled($value = settings('partners.'.$key)) ? (string) $value : null;
    }

    public function image(): ?string
    {
        return media_url(settings('partners.image'), 'large');
    }

    public function url(string $path, array $query = []): string
    {
        return url(($this->data['base'] ?? '').'/'.$path).($query ? '?'.http_build_query($query) : '');
    }

    /** @return list<array{label: string, url: string}> */
    public function breadcrumbs(string $title, string $path): array
    {
        return [['label' => __('theme::theme.home'), 'url' => url(($this->data['base'] ?? '') ?: '/')], ['label' => $title, 'url' => $this->url($path)]];
    }

    /** @return list<array{label: string, url: string}> */
    public function quickLinks(): array
    {
        return link_list(settings('partners.quick_links'));
    }

    // /partners ------------------------------------------------------------------------------

    /** @return list<array{label: string, url: string, current: bool}> "All" and the types present; a link to the discounts */
    public function tabs(): array
    {
        $types = collect($this->data['types'] ?? []);
        $type = $this->data['type'] ?? null;
        $tabs = $types->count() > 1 ? [
            ['label' => __('partners::partners.page.all'), 'url' => $this->url(Partner::PATH), 'current' => $type === null],
            ...$types->map(fn (string $t): array => ['label' => __("partners::partners.types.{$t}"), 'url' => $this->url(Partner::PATH, ['type' => $t]), 'current' => $type === $t])->all(),
        ] : [];
        if ($this->data['hasDiscounts'] ?? false) {
            $tabs[] = ['label' => __('partners::partners.see_discounts'), 'url' => $this->url(Partner::DISCOUNT_PATH), 'current' => false];
        }

        return $tabs;
    }

    /** @return list<array{name: string, logo: ?string, url: ?string, domain: ?string, text: ?string, international: bool, type: string, kind: string}> */
    public function logos(): array
    {
        return collect($this->data['partners'] ?? [])->map(fn (Partner $p): array => [
            'name' => (string) $p->name,
            'logo' => $p->firstMedia('logo')?->url('medium') ?? $p->firstMedia('logo')?->url(),
            'url' => $p->website_url ?: null,
            'domain' => $p->website_url ? preg_replace('/^www\./i', '', (string) parse_url((string) $p->website_url, PHP_URL_HOST)) ?: null : null,
            'text' => filled($p->description) ? Str::limit(strip_tags((string) $p->description), 180) : null,
            'international' => (bool) $p->is_international,
            'type' => __("partners::partners.types.{$p->partner_type}"),   // the tag: Accreditation, Membership ...
            'kind' => (string) $p->partner_type,
        ])->values()->all();
    }

    // /student-discounts ---------------------------------------------------------------------

    /** The chosen category's group: the asked one, else the first */
    protected function group(): ?array
    {
        $groups = collect($this->data['groups'] ?? []);
        $asked = (string) request()->query('category');

        return $groups->first(fn (array $g): bool => $asked !== '' && $g['term']?->slug() === $asked) ?? $groups->first();
    }

    /** @return list<array{id: string, label: string, url: string, current: bool, arrow: bool}> the categories with their partners */
    public function categories(): array
    {
        $current = $this->group();

        return collect($this->data['groups'] ?? [])->map(fn (array $g): array => [
            'id' => (string) ($g['term']?->slug() ?? 'other'),
            'label' => ($g['term']?->name ?? __('partners::partners.page.all')).' ('.$g['partners']->count().')',
            'url' => $this->url(Partner::DISCOUNT_PATH, $g['term'] ? ['category' => $g['term']->slug()] : []),
            'current' => $current !== null && $current === $g,
            'arrow' => false,
        ])->values()->all();
    }

    public function categoryName(): ?string
    {
        return $this->group()['term']?->name ?? null;
    }

    public function search(): string
    {
        return trim((string) request()->query('q'));
    }

    /** @return list<array{name: string, discount: ?string, short: ?string, terms: ?string, url: ?string, until: ?string, logo: ?string}> */
    public function discounts(): array
    {
        $partners = collect($this->group()['partners'] ?? []);
        if (($q = mb_strtolower($this->search())) !== '') {
            $partners = $partners->filter(fn (Partner $p): bool => str_contains(mb_strtolower((string) $p->name.' '.$p->website_url.' '.$p->discount_value), $q));
        }

        return $partners->map(fn (Partner $p): array => [
            'name' => (string) $p->name,
            'discount' => filled($p->discount_value) ? (string) $p->discount_value : null,
            'short' => filled($p->discount_value) ? Str::limit((string) $p->discount_value, 100) : null,
            'terms' => filled($p->discount_terms) ? (string) $p->discount_terms : null,
            'url' => $p->website_url ?: null,
            'until' => $p->valid_until?->translatedFormat('j M Y'),
            'logo' => $p->firstMedia('logo')?->url('thumb') ?? $p->firstMedia('logo')?->url(),
        ])->values()->all();
    }

    /** The long discount or the terms need opening (DIU's "Show More") */
    public function hasMore(array $row): bool
    {
        return $row['terms'] !== null || mb_strlen((string) $row['discount']) > 100;
    }
}
