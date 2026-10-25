<?php

namespace Modules\Admission\Support;

use Modules\Admission\Blocks\HighlightsBlock;
use Modules\Admission\Models\AdmissionContactGroup;
use Modules\Admission\Models\AdmissionHighlight;
use Modules\Admission\Models\AdmissionStep;
use Modules\Admission\Models\Circular;

/**
 * What the admission page blocks show, ready for a theme (`$admission` in admission::blocks.*): the circulars as
 * DIU's notice card (each date with Active / Upcoming / Expired), the Apply / Request information links, the
 * information cards by category, the steps and the contacts. Themes hold no PHP logic (T-1).
 */
class AdmissionBlocks
{
    /** DIU's notice rows: the date and the word it shows while ahead */
    public const ROWS = ['apply_ends_on' => 'active', 'test_on' => 'upcoming', 'admission_on' => 'active', 'classes_start_on' => 'upcoming'];

    public function __construct(protected array $data = []) {}

    /** @return list<array{title: string, url: string, closed: bool, rows: list<array{label: string, date: string, iso: string, status: string, past: bool}>}> */
    public function circulars(): array
    {
        $data = (array) ($this->data['data'] ?? []);

        return Circular::query()->frontendVisible()->when($data['running_only'] ?? true, fn ($q) => $q->running())->ordered()->get()
            ->sortBy(fn (Circular $c): int => $c->isClosed() ? 1 : 0)->take((int) ($data['limit'] ?? 3) ?: 3)
            ->map(fn (Circular $c): array => static::card($c))->values()->all();
    }

    /** One circular as DIU's notice: name, address, open or closed, each date with its status. @return array{title: string, url: string, closed: bool, session: ?string, rows: list<array{label: string, date: string, iso: string, status: string, past: bool}>} */
    public static function card(Circular $c): array
    {
        return [
            'title' => (string) $c->title,
            'url' => $c->url(),
            'closed' => $c->isClosed(),
            'session' => filled($c->session_label) ? (string) $c->session_label : null,
            'rows' => collect(self::ROWS)->filter(fn (string $word, string $key): bool => $c->{$key} !== null)
                ->map(fn (string $word, string $key): array => [
                    'label' => __("admission::admission.dates.{$key}"),
                    'date' => $c->{$key}->translatedFormat('F j, Y'),
                    'iso' => $c->{$key}->toDateString(),
                    'past' => $past = $c->{$key}->copy()->endOfDay()->isPast(),
                    'status' => __('admission::admission.status.'.($past ? 'expired' : $word)),
                ])->values()->all(),
        ];
    }

    public function circularsUrl(): string
    {
        return url('/'.Circular::PATH);
    }

    /** @return list<array{url: string, label: string, external: bool, primary: bool}> the Apply block's buttons, in its order */
    public function links(): array
    {
        $all = [
            'apply' => [settings('admission.apply_url'), __('admission::admission.apply_now'), true, true],
            'info' => [settings('admission.info_request_url'), __('admission::admission.request_info'), false, false],
            'whatsapp' => [settings('admission.whatsapp_group_url'), __('admission::admission.join_whatsapp'), true, false],
            'result' => [url('/admission/result'), __('admission::admission.result_page.title'), false, false],
        ];

        return collect((array) ($this->data['data']['buttons'] ?? ['apply', 'info']))
            ->filter(fn ($key): bool => filled($all[$key][0] ?? null))
            ->map(fn (string $key): array => ['url' => (string) $all[$key][0], 'label' => $all[$key][1], 'external' => $all[$key][2], 'primary' => $all[$key][3]])
            ->values()->all();
    }

    /** @return list<array{category: ?string, items: list<array{title: string, url: ?string, external: bool, image: ?string}>}> */
    public function highlights(): array
    {
        return HighlightsBlock::groups((array) ($this->data['data'] ?? []))->map(fn (array $g): array => [
            'category' => $g['category'] ? (string) $g['category']->name : null,
            'items' => $g['items']->map(fn (AdmissionHighlight $h): array => [
                'title' => (string) $h->title,
                'url' => $h->url ?: null,
                'external' => (bool) $h->open_in_new_tab || str_starts_with((string) $h->url, 'http'),
                'image' => $h->firstMedia('image')?->url('thumb'),
            ])->values()->all(),
        ])->values()->all();
    }

    /** @return list<array{title: string, text: ?string}> */
    public function steps(): array
    {
        return AdmissionStep::query()->frontendVisible()->ordered()->get()
            ->map(fn (AdmissionStep $s): array => ['title' => (string) $s->title, 'text' => filled($s->description) ? (string) $s->description : null])->all();
    }

    /** @return list<array{name: string, people: list<array{name: string, designation: ?string, photo: ?string, phone: ?string, tel: ?string, ext: ?string, email: ?string}>}> */
    public function contacts(): array
    {
        $groups = (array) ($this->data['data']['groups'] ?? []);

        return AdmissionContactGroup::query()->frontendVisible()->when($groups !== [], fn ($q) => $q->whereKey(array_map('intval', $groups)))->ordered()->get()
            ->map(fn (AdmissionContactGroup $g): array => [
                'name' => (string) $g->name,
                'people' => $g->people(AdmissionContactGroup::ROLE)->map(fn ($p): array => [
                    'name' => (string) $p->name(),
                    'designation' => $p->designation() ?: null,
                    'photo' => $p->photoUrl(),
                    'phone' => $p->phone() ?: null,
                    'tel' => $p->phone() ? preg_replace('/[^0-9+]/', '', (string) $p->phone()) : null,
                    'ext' => $p->extension() ?: null,
                    'email' => $p->email() ?: null,
                ])->values()->all(),
            ])->filter(fn (array $g): bool => $g['people'] !== [])->values()->all();
    }
}
