<?php

namespace Modules\Transport\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Transport\Settings\TransportSettings;
use Throwable;

/**
 * DIU's live transport system stays the owner of the data (DB-06 v1.1 §2, owner 2026-10-17); this
 * reads its public API and turns the answer into plain arrays for the website. The answer is
 * cached; the last good one is kept apart, so the page still shows the routes when the API is
 * down ("as of …"). Only arrays go into the cache (no objects).
 */
class TransportApi
{
    public const CACHE = 'transport.api';

    public const LAST_GOOD = 'transport.api.last_good';

    public const GROUPS = ['regular_road', 'shuttle_road', 'friday_road'];

    /**
     * @return array{ok: bool, stale: bool, fetched_at: ?string, title: ?string, semester: ?string, policy_pdf: ?string,
     *               service: ?string, groups: array<string, list<array>>}
     */
    public function timetable(): array
    {
        $cached = Cache::get(self::CACHE);
        if ($cached === null) {
            $cached = $this->fetch();
            if ($cached !== null) {
                Cache::put(self::CACHE, $cached, now()->addMinutes(max(1, (int) (settings('transport.cache_minutes') ?: 30))));
                Cache::forever(self::LAST_GOOD, $cached);
            } else {
                $cached = ['down' => true];
                Cache::put(self::CACHE, $cached, now()->addMinutes(5));   // a down API is not asked on every visit
            }
        }

        if (! isset($cached['down'])) {
            return $cached + ['ok' => true, 'stale' => false];
        }
        $last = Cache::get(self::LAST_GOOD);

        return $last !== null ? $last + ['ok' => true, 'stale' => true] : $this->empty();
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE);
    }

    /** One call to the API, normalised; null when it fails or does not look right. */
    public function fetch(): ?array
    {
        try {
            $response = Http::timeout(max(2, (int) (settings('transport.timeout') ?: 8)))->acceptJson()
                ->get((string) (settings('transport.api_url') ?: TransportSettings::DEFAULT_URL));
            $json = $response->successful() ? $response->json() : null;
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        if (! is_array($json) || ! ($json['status'] ?? false) || ! is_array($json['data'] ?? null)) {
            return null;
        }

        return self::normalise($json);
    }

    /** The API's answer as the website needs it. */
    public static function normalise(array $json): array
    {
        $groups = [];
        foreach (self::GROUPS as $group) {
            $roads = collect((array) Arr::get($json, "data.{$group}", []))
                ->filter(fn ($road): bool => is_array($road) && filled($road['name'] ?? null))
                ->map(fn (array $road): array => [
                    'id' => (int) ($road['id'] ?? 0),
                    'name' => trim((string) $road['name']),
                    'from' => trim((string) ($road['start_stop'] ?? '')),
                    'to' => trim((string) ($road['end_stop'] ?? '')),
                    'order' => (int) ($road['order'] ?? 0),
                    'map' => self::safeUrl($road['map_url'] ?? null),
                    'days_off' => array_values(array_filter(array_map('strval', (array) ($road['day_off'] ?? [])))),
                    'notes' => self::notes((string) ($road['message'] ?? '')),
                    'service' => (string) ($road['service_name'] ?? ''),
                    'stops' => collect((array) ($road['stops'] ?? []))->filter(fn ($s): bool => is_array($s) && filled($s['name'] ?? null))
                        ->sortBy(fn (array $s): int => (int) ($s['order'] ?? 0))->map(fn (array $s): string => trim((string) $s['name']))->values()->all(),
                    'to_campus' => self::times(Arr::get($road, 'schedules.from_home', [])),
                    'from_campus' => self::times(Arr::get($road, 'schedules.from_campus', [])),
                ])
                ->sortBy('order')->values()->all();
            if ($roads !== []) {
                $groups[$group] = $roads;
            }
        }

        return [
            'fetched_at' => now()->toIso8601String(),
            'title' => self::text($json['title'] ?? null),
            'semester' => self::text($json['semester'] ?? null),
            'policy_pdf' => self::safeUrl($json['rules_policy_pdf'] ?? null),
            'service' => self::text($json['active_service_name'] ?? null),
            'groups' => $groups,
        ];
    }

    /** @return list<array{time: string, type: string, off: list<string>}> in time order */
    protected static function times(mixed $schedules): array
    {
        return collect((array) $schedules)->filter(fn ($s): bool => is_array($s) && preg_match('/^\d{1,2}:\d{2}/', (string) ($s['time'] ?? '')))
            ->map(fn (array $s): array => ['time' => substr((string) $s['time'], 0, 5), 'type' => (string) ($s['schedule_type'] ?? 'regular'), 'off' => array_values((array) ($s['off_day'] ?? []))])
            ->sortBy('time')->values()->all();
    }

    /** "Note:\r\n1. …\r\n2. …" → the numbered lines */
    protected static function notes(string $message): array
    {
        return collect(preg_split('/\R/', trim($message)) ?: [])
            ->map(fn (string $line): string => trim(preg_replace('/^\s*(note\s*:|\d+\.)\s*/i', '', $line) ?? ''))
            ->filter()->values()->all();
    }

    protected static function safeUrl(mixed $url): ?string
    {
        $url = is_string($url) ? trim($url) : '';

        return preg_match('#^https://#i', $url) ? $url : null;
    }

    protected static function text(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }

    protected function empty(): array
    {
        return ['ok' => false, 'stale' => false, 'fetched_at' => null, 'title' => null, 'semester' => null, 'policy_pdf' => null, 'service' => null, 'groups' => []];
    }
}
