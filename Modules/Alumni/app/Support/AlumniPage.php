<?php

namespace Modules\Alumni\Support;

use Illuminate\Support\Str;
use Modules\Alumni\Models\Alumnus;

/**
 * What the alumni pages show, ready for a theme (`$page` in alumni::index, alumni::show, alumni::submit
 * and the "Our Pride Alumni" section), in DIU's AlumniList look: a card per graduate — photo (3:4),
 * name, what they do, where, the department — Pride first; filters; the profile with its story and
 * video. Title, text, picture and the alumni portal link from Settings › Alumni. Themes hold no PHP logic (T-1).
 */
class AlumniPage
{
    public function __construct(protected array $data) {}

    /** The main site's own title; a Department or Program site's page is just "Alumni" (DIU's department pages) */
    public function title(): string
    {
        return $this->microsite() ? __('alumni::alumni.plural') : (string) (settings('alumni.title') ?: __('alumni::alumni.plural'));
    }

    public function text(): ?string
    {
        return ! $this->microsite() && filled($text = settings('alumni.text')) ? (string) $text : null;
    }

    public function image(): ?string
    {
        return media_url(settings('alumni.image'), 'large');
    }

    /** @return array{label: string, url: string}|null DIU's "Join the alumni portal" */
    public function portal(): ?array
    {
        $url = trim((string) settings('alumni.portal_url'));

        return $url !== '' ? ['label' => (string) (settings('alumni.portal_label') ?: $url), 'url' => $url] : null;
    }

    public function microsite(): bool
    {
        return (bool) ($this->data['microsite'] ?? false);
    }

    public function listingUrl(): string
    {
        return url(($this->data['base'] ?? '').'/'.Alumnus::PATH);
    }

    public function submitUrl(): ?string
    {
        return $this->microsite() ? null : url('/'.Alumnus::PATH.'/submit');
    }

    /** @return list<array{label: string, url: string}> */
    public function breadcrumbs(?string $last = null): array
    {
        return array_values(array_filter([
            ['label' => __('theme::theme.home'), 'url' => url(($this->data['base'] ?? '') ?: '/')],
            ['label' => __('alumni::alumni.plural'), 'url' => $this->listingUrl()],
            $last !== null ? ['label' => $last, 'url' => request()->url()] : null,
        ]));
    }

    /**
     * The filters that have choices: name, label, the "all" text and the options (value => label).
     *
     * @return list<array{name: string, label: string, options: array<string, string>, value: string}>
     */
    public function filters(): array
    {
        $request = request();
        $filters = [
            ['name' => 'department', 'label' => __('alumni::alumni.page.department'), 'options' => collect($this->data['departments'] ?? [])->mapWithKeys(fn ($d): array => [(string) $d->id => (string) $d->name])->all()],
            ['name' => 'program', 'label' => __('alumni::alumni.page.program'), 'options' => collect($this->data['programs'] ?? [])->mapWithKeys(fn ($p): array => [(string) $p->id => (string) $p->name])->all()],
            ['name' => 'batch', 'label' => __('alumni::alumni.page.batch'), 'options' => collect($this->data['batches'] ?? [])->mapWithKeys(fn ($b): array => [(string) $b => (string) $b])->all()],
            ['name' => 'year', 'label' => __('alumni::alumni.page.year'), 'options' => collect($this->data['years'] ?? [])->mapWithKeys(fn ($y): array => [(string) $y => (string) $y])->all()],
            ['name' => 'job', 'label' => __('alumni::alumni.page.job'), 'options' => collect(Alumnus::JOB_TYPES)->mapWithKeys(fn (string $j): array => [$j => __("alumni::alumni.job_types.{$j}")])->all()],
        ];

        return array_values(array_map(fn (array $f): array => $f + ['value' => (string) $request->query($f['name'])],
            array_filter($filters, fn (array $f): bool => count($f['options']) > 1 || ($f['options'] !== [] && $f['name'] === 'job'))));
    }

    /** The Pride section as a sliding row (else a grid), as the section's settings say */
    public function slider(): bool
    {
        return ($this->data['settings']['display'] ?? 'slider') === 'slider';
    }

    public function total(): int
    {
        return (int) ($this->data['alumni'] ?? null)?->total();
    }

    /** @return list<array<string, mixed>> */
    public function cards(): array
    {
        $alumni = $this->data['alumni'] ?? $this->data['items'] ?? [];

        return collect(method_exists($alumni, 'items') ? $alumni->items() : $alumni)->map(fn (Alumnus $a): array => $this->card($a))->values()->all();
    }

    /**
     * @return array{name: string, url: string, photo: ?string, initials: string, designation: ?string, organization: ?string,
     *     department: ?string, pride: bool, batch: ?string, year: ?int}
     */
    public function card(Alumnus $alumnus): array
    {
        $department = $alumnus->department();

        return [
            'name' => (string) $alumnus->name,
            'url' => url($alumnus->url()),
            'photo' => $alumnus->personPresenter()->photoUrl(),
            'initials' => $this->initials((string) $alumnus->name),
            'designation' => filled($alumnus->current_designation) ? (string) $alumnus->current_designation : null,
            'organization' => filled($alumnus->organization) ? (string) $alumnus->organization : null,
            'department' => $department ? (string) ($department->short_name ?: $department->name) : null,
            'pride' => (bool) $alumnus->is_pride,
            'batch' => filled($alumnus->batch) ? (string) $alumnus->batch : null,
            'year' => $alumnus->passing_year ? (int) $alumnus->passing_year : null,
        ];
    }

    /** One graduate's page: the card and the rest */
    public function profile(): array
    {
        /** @var Alumnus $alumnus */
        $alumnus = $this->data['alumnus'];
        $person = $alumnus->personPresenter();
        $program = $alumnus->program();
        $department = $alumnus->department();
        $links = array_filter((array) $alumnus->social_links);

        return $this->card($alumnus) + [
            'study' => array_values(array_filter([
                $program ? ['label' => __('alumni::alumni.page.program'), 'value' => (string) $program->name, 'url' => url($program->url())] : null,
                $department ? ['label' => __('alumni::alumni.page.department'), 'value' => (string) $department->name, 'url' => url($department->url())] : null,
                $alumnus->batch ? ['label' => __('alumni::alumni.page.batch'), 'value' => (string) $alumnus->batch, 'url' => null] : null,
                $alumnus->passing_year ? ['label' => __('alumni::alumni.page.year'), 'value' => (string) $alumnus->passing_year, 'url' => null] : null,
                $alumnus->job_type ? ['label' => __('alumni::alumni.page.job'), 'value' => __("alumni::alumni.job_types.{$alumnus->job_type}"), 'url' => null] : null,
            ])),
            'email' => $person->email(),
            'phone' => $person->phone(),
            'address' => $alumnus->show_address && filled($alumnus->office_address) ? (string) $alumnus->office_address : null,
            'social' => collect(Alumnus::SOCIAL)->filter(fn (string $n): bool => filled($links[$n] ?? null) && preg_match('#^https?://#i', (string) $links[$n]))
                ->map(fn (string $n): array => ['label' => __("alumni::alumni.social.{$n}"), 'url' => (string) $links[$n], 'icon' => $n])->values()->all(),
            'video' => $alumnus->videoEmbed(),
            'story' => filled($alumnus->story) ? Str::sanitizeHtml((string) $alumnus->story) : null,
        ];
    }

    protected function initials(string $name): string
    {
        $words = array_values(array_filter(preg_split('/\s+/', trim((string) preg_replace('/^((dr|mr|mrs|ms|md|engr)\.?\s+)+/i', '', $name))) ?: []));

        return $words === [] ? '?' : Str::upper(Str::substr($words[0], 0, 1).(count($words) > 1 ? Str::substr(end($words), 0, 1) : ''));
    }
}
