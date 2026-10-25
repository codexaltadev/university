<?php

namespace Modules\Admission\Support;

use Illuminate\Support\Str;
use Modules\Admission\Models\Circular;
use Modules\Admission\Settings\AdmissionSettings;

/**
 * What the module's own pages show, ready for a theme (`$page` in admission::result, ::contact, ::circulars, ::circular):
 * DIU's /admission-test-result (intro, links, picture, "Need Assistance?"), /admission-contact (the office, its map,
 * the contact groups) and the circulars as DIU's notices; texts and links from Settings › Admission. Themes hold no PHP logic (T-1).
 */
class AdmissionPage
{
    public function __construct(protected array $data = []) {}

    /** @return list<array{label: string, url: string}> Home › Admission › … */
    public function breadcrumbs(?string $title = null, ?string $parent = null, ?string $parentUrl = null): array
    {
        return array_values(array_filter([
            ['label' => __('theme::theme.home'), 'url' => url('/')],
            ['label' => __('admission::admission.title'), 'url' => url('/admission')],
            $parent ? ['label' => $parent, 'url' => (string) $parentUrl] : null,
            $title ? ['label' => $title, 'url' => request()->url()] : null,
        ]));
    }

    public function intro(): ?string
    {
        return filled($intro = settings('admission.result_page_intro')) ? Str::sanitizeHtml((string) $intro) : null;
    }

    /** @return list<array{label: string, url: string}> the result page's "Admission Information" links */
    public function resultLinks(): array
    {
        return link_list((array) settings('admission.result_links', []));
    }

    public function resultImage(): ?string
    {
        return media_url(settings('admission.result_image'), 'large');
    }

    public function applyUrl(): ?string
    {
        return filled($url = settings('admission.apply_url')) ? (string) $url : null;
    }

    /** @return array{name: ?string, address: ?string, helpline: ?string, tel: ?string, email: ?string, map: ?string} the Admission Office */
    public function office(): array
    {
        $helpline = filled($h = settings('admission.helpline')) ? (string) $h : null;
        $map = (string) settings('admission.map_url');

        return [
            'name' => filled($n = settings('admission.office_name')) ? (string) $n : null,
            'address' => filled($a = settings('admission.office_address')) ? (string) $a : null,
            'helpline' => $helpline,
            'tel' => $helpline ? preg_replace('/[^0-9+]/', '', $helpline) : null,
            'email' => filled($e = settings('admission.email')) ? (string) $e : null,
            'map' => preg_match(AdmissionSettings::MAP_PATTERN, $map) ? $map : null,
        ];
    }

    /** @return list<array{platform: string, url: string, label: ?string}> the site's social links ("Connect With Us") */
    public function social(): array
    {
        return array_values(array_filter((array) settings('social.links', []), fn ($l): bool => is_array($l) && filled($l['url'] ?? null)));
    }

    /** @return list<array{name: string, people: list<array>}> every contact group with its people */
    public function contacts(): array
    {
        return (new AdmissionBlocks)->contacts();
    }

    /** @return list<array> the circulars (open first) as DIU's notices */
    public function circulars(): array
    {
        return collect($this->data['circulars'] ?? [])->map(fn (Circular $c): array => AdmissionBlocks::card($c))->values()->all();
    }

    public function circularsUrl(): string
    {
        return url('/'.Circular::PATH);
    }

    /** @return array{title: string, closed: bool, session: ?string, rows: list<array>, body: ?string, pdf: ?string, apply: ?string} */
    public function circular(): array
    {
        /** @var Circular $c */
        $c = $this->data['circular'];

        return [
            ...AdmissionBlocks::card($c),
            'body' => filled($c->content) ? Str::sanitizeHtml((string) $c->content) : null,
            'pdf' => $c->firstMedia('pdf')?->url(),
            'apply' => $c->isClosed() ? null : $this->applyUrl(),
        ];
    }
}
