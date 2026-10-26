<?php

namespace Modules\Transport\Support;

use Illuminate\Support\Carbon;

/**
 * What /transport shows, ready for a theme (`$transport` in transport::index), as DIU's /transport: the hero line, the
 * Transport Pass, the running schedule (title, semester, rules), Regular / Shuttle / Friday route cards (both departure
 * lists with their 12-hour times and the days a trip does not run, the stops in order, the notes, the map), the stop
 * search and the fees. Themes hold no PHP logic (T-1).
 */
class TransportView
{
    public function __construct(protected array $data) {}

    public function title(): string
    {
        return __('transport::transport.page_title');
    }

    /** @return list<array{label: string, url: string}> */
    public function breadcrumbs(): array
    {
        return [
            ['label' => __('theme::theme.home'), 'url' => url('/')],
            ['label' => $this->title(), 'url' => url('/'.TransportWebsite::PATH)],
        ];
    }

    public function intro(): ?string
    {
        return $this->data['page']['intro'] ?? null;
    }

    /** @return array{title: string, subtitle: ?string, how: ?string, why: ?string, image: ?string, alt: string}|null */
    public function pass(): ?array
    {
        $page = $this->data['page'] ?? [];
        if (blank($page['pass_title'] ?? null)) {
            return null;
        }
        $image = $page['pass_image'] ?? null;

        return [
            'title' => (string) $page['pass_title'],
            'subtitle' => $page['pass_subtitle'] ?? null,
            'how' => $page['pass_how'] ?? null,
            'why' => $page['pass_why'] ?? null,
            'image' => $image ? ($image->url('large') ?? $image->url()) : null,
            'alt' => (string) ($image?->alt_text ?: $page['pass_title']),
        ];
    }

    /** @return array{title: string, subtitle: ?string, semester: ?string, policy: ?string, stale: ?string}|null the running schedule, null when the routes are not there */
    public function schedule(): ?array
    {
        $t = $this->data['timetable'] ?? [];
        if (! ($t['ok'] ?? false)) {
            return null;
        }

        return [
            'title' => __('transport::transport.schedule_title', ['service' => $t['service'] ?: __('transport::transport.groups.regular_road')]),
            'subtitle' => $t['title'] && ! str_contains(strtolower((string) $t['title']), 'regular') ? (string) $t['title'] : null,
            'semester' => $t['semester'] ?: null,
            'policy' => $t['policy_pdf'] ?: null,
            'stale' => ($t['stale'] ?? false) && $t['fetched_at'] ? __('transport::transport.stale', ['time' => Carbon::parse($t['fetched_at'])->diffForHumans()]) : null,
        ];
    }

    /** The tab open first (?service=…) */
    public function openGroup(): ?string
    {
        return $this->data['group'] ?? null;
    }

    /**
     * @return list<array{key: string, label: string, routes: list<array{id: int, name: string, from: ?string, to: ?string, days_off: ?string,
     *     to_campus: list<array{label: string, time: string, off: list<string>, shuttle: bool}>, from_campus: list<array>, stops: list<string>, notes: list<string>, map: ?string}>}>
     */
    public function groups(): array
    {
        $groups = [];
        foreach ((array) ($this->data['timetable']['groups'] ?? []) as $key => $routes) {
            $groups[] = [
                'key' => (string) $key,
                'label' => __("transport::transport.groups.{$key}"),
                'routes' => array_map(fn (array $r): array => [
                    'id' => (int) $r['id'],
                    'name' => (string) $r['name'],
                    'from' => $r['from'] ?: null,
                    'to' => $r['to'] ?: null,
                    'days_off' => $r['days_off'] ? implode(', ', $r['days_off']) : null,
                    'to_campus' => $this->trips($r['to_campus'], $key),
                    'from_campus' => $this->trips($r['from_campus'], $key),
                    'stops' => array_values($r['stops']),
                    'notes' => array_values($r['notes']),
                    'map' => $r['map'] ?: null,
                ], $routes),
            ];
        }

        return $groups;
    }

    /** For the stop search (partials.finder): every route with its stops @return list<array{id: int, name: string, group: string, service: string, stops: list<string>}> */
    public function finder(): array
    {
        return collect($this->groups())->flatMap(fn (array $g) => collect($g['routes'])->map(fn (array $r): array => [
            'id' => $r['id'], 'name' => $r['name'], 'group' => $g['key'], 'service' => $g['label'], 'stops' => $r['stops'],
        ]))->values()->all();
    }

    /** @return list<string> every stop's name */
    public function stops(): array
    {
        return array_values($this->data['stops'] ?? []);
    }

    /** @return list<array{route: string, tri: string, bi: string}> */
    public function fees(): array
    {
        return array_map(fn (array $f): array => ['route' => (string) $f['route'], 'tri' => (string) ($f['tri'] ?? '—'), 'bi' => (string) ($f['bi'] ?? '—')], $this->data['page']['fees'] ?? []);
    }

    public function feesNote(): ?string
    {
        return $this->data['page']['fees_note'] ?? null;
    }

    /** @return list<array{label: string, time: string, off: list<string>, shuttle: bool}> 12-hour labels; "time" (H:i) and "off" (days) let the page grey out a trip that has gone or does not run today, as DIU does */
    protected function trips(array $trips, string $group): array
    {
        return array_map(fn (array $t): array => [
            'label' => Carbon::createFromFormat('H:i', $t['time'])->format('g:i A'),
            'time' => $t['time'],
            'off' => array_values((array) $t['off']),
            'shuttle' => $t['type'] === 'shuttle' && $group !== 'shuttle_road',
        ], $trips);
    }
}
