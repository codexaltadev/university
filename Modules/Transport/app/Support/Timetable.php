<?php

namespace Modules\Transport\Support;

use Illuminate\Support\Collection;
use Modules\Transport\Models\Departure;
use Modules\Transport\Models\Route;
use Modules\Transport\Models\ServiceType;
use Modules\Transport\Models\Stop;
use Modules\Transport\Models\Variant;

/**
 * The timetable as it runs now (DB-06 v1.2 §2): every active route with its running-service variant
 * (else the default service's) — stops in order, days off, message, departures both ways. One
 * shape for the website (`website()`) and the old frontend's public API (`api()`, the same JSON as
 * DIU's transport system gave, so its frontend only changes the address).
 */
class Timetable
{
    /** route_group → the API / website group key */
    public const GROUP_KEYS = ['regular' => 'regular_road', 'shuttle' => 'shuttle_road', 'friday' => 'friday_road'];

    /** @return list<array{route: Route, variant: Variant}> */
    public function routes(): array
    {
        $running = ServiceType::running();
        $default = ServiceType::default();

        return Route::query()->where('is_active', true)->ordered()
            ->with(['variants' => fn ($q) => $q->where('is_active', true)->with(['stops' => fn ($s) => $s->where('transport_stops.is_active', true), 'departures' => fn ($d) => $d->where('is_active', true), 'serviceType'])])
            ->get()
            ->map(fn (Route $route): ?array => ($variant = $route->variantFor($running, $default)) ? ['route' => $route, 'variant' => $variant] : null)
            ->filter()->values()->all();
    }

    /** For /transport and the homepage section — the shape the views use. */
    public function website(): array
    {
        $groups = [];
        foreach ($this->routes() as ['route' => $route, 'variant' => $variant]) {
            $stops = $variant->stops->map(fn (Stop $s): string => (string) $s->name)->values();
            $groups[self::GROUP_KEYS[$route->route_group]][] = [
                'id' => $route->id,
                'name' => (string) $route->name,
                'from' => (string) $stops->first(),
                'to' => (string) $stops->last(),
                'order' => (int) $route->sort_order,
                'map' => $route->map_url,
                'days_off' => (array) $variant->days_off,
                'notes' => self::notes((string) ($variant->message ?: $route->message)),
                'service' => (string) $variant->serviceType?->name,
                'stops' => $stops->all(),
                'to_campus' => $this->times($variant->departures->where('direction', 'to_campus')),
                'from_campus' => $this->times($variant->departures->where('direction', 'from_campus')),
            ];
        }
        $running = ServiceType::running();

        return [
            'ok' => $groups !== [],
            'stale' => false,
            'fetched_at' => null,
            'title' => self::setting('schedule_title'),
            'semester' => self::setting('semester'),
            'policy_pdf' => settings()->media('transport.policy_file')?->url(),
            'service' => $running ? (string) $running->name : null,
            'groups' => array_filter(array_map(fn (string $key) => $groups[$key] ?? null, array_combine(self::GROUP_KEYS, self::GROUP_KEYS))),   // Regular, Shuttle, Friday
        ];
    }

    /** GET /api/v2/public/transport — the old transport system's answer, field for field. */
    public function api(): array
    {
        $running = ServiceType::running();
        $data = array_fill_keys(array_values(self::GROUP_KEYS), []);
        $routes = $this->routes();
        foreach ($routes as ['route' => $route, 'variant' => $variant]) {
            $stops = $variant->stops->values();
            $schedule = fn (string $direction, string $legacy): array => $variant->departures->where('direction', $direction)->values()
                ->map(fn (Departure $d, int $i): array => ['id' => $d->id, 'time' => substr((string) $d->departs_at, 0, 8), 'order' => $i + 1, 'direction' => $legacy, 'schedule_type' => $d->trip_type, 'off_day' => (array) $d->days_off])->all();
            $data[self::GROUP_KEYS[$route->route_group]][] = [
                'id' => $route->id,
                'name' => (string) $route->name,
                'start_stop' => (string) $stops->first()?->name,
                'end_stop' => (string) $stops->last()?->name,
                'order' => (int) $route->sort_order,
                'map_url' => $route->map_url,
                'tag' => self::GROUP_KEYS[$route->route_group],
                'day_off' => (array) $variant->days_off,
                'message' => (string) ($variant->message ?: $route->message),
                'service_name' => (string) $variant->serviceType?->name,
                'service_code' => (string) $variant->serviceType?->code,
                'stops' => $stops->map(fn (Stop $s, int $i): array => ['id' => $s->id, 'name' => (string) $s->name, 'order' => $i + 1])->all(),
                'schedules' => ['from_home' => $schedule('to_campus', 'from_home'), 'from_campus' => $schedule('from_campus', 'from_campus')],
            ];
        }

        return [
            'status' => true,
            'title' => self::setting('schedule_title'),
            'semester' => self::setting('semester'),
            'organization' => (string) settings('general.site_name'),
            'rules_policy_pdf' => settings()->media('transport.policy_file')?->url(),
            'message' => 'List of transport roads v2',
            'active_service_name' => $running ? (string) $running->name : null,
            'active_service_code' => $running?->code,
            'total_roads' => count($routes),
            'data' => $data,
        ];
    }

    /** @return list<array{time: string, type: string, off: list<string>}> */
    protected function times(Collection $departures): array
    {
        return $departures->sortBy('departs_at')->map(fn (Departure $d): array => ['time' => $d->time(), 'type' => $d->trip_type, 'off' => (array) $d->days_off])->values()->all();
    }

    /** "Note:\n1. …\n2. …" → the numbered lines */
    public static function notes(string $message): array
    {
        return collect(preg_split('/\R/', trim($message)) ?: [])
            ->map(fn (string $line): string => trim(preg_replace('/^\s*(note\s*:|\d+\.)\s*/i', '', $line) ?? ''))
            ->filter()->values()->all();
    }

    protected static function setting(string $key): ?string
    {
        $value = settings()->translate(settings('transport.'.$key));

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
