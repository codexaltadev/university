<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Legacy\Migration\Support\Normalizer;
use Modules\Transport\Models\Route;
use Modules\Transport\Models\ServiceType;
use Modules\Transport\Models\Stop;
use Modules\Transport\Models\Variant;
use Platform\Media\MediaItem;

/**
 * S18T Transport (DB-06 v1.2 §2, owner 2026-10-17: the old transport system goes): service types
 * (the one running now → Settings › Transport), stops, routes (`tag` → group); a road's own stops
 * and times → the default service's variant, `road_services` → that service's variant; stop order =
 * the road's start stop, the pivot's id order (no order column there), the road's end stop (DSC was
 * kept only on the road); `day_of_week` = days off; times were
 * shared by roads there, here each variant has its own. `transport_settings` → title, semester,
 * rules PDF (after S03). Safe to run again: a variant's stops and times are replaced.
 */
class S18Transport extends Step
{
    public static function key(): string
    {
        return 'S18T';
    }

    public static function title(): string
    {
        return 'Transport';
    }

    public static function requires(): array
    {
        return ['transport'];
    }

    protected function handle(): void
    {
        if (! Schema::connection('legacy')->hasTable('roads')) {
            $this->report->review('No transport tables in the legacy database: nothing to migrate.');

            return;
        }

        // Service types
        $services = [];
        $running = null;
        foreach ($this->rows('transport_services') as $row) {
            $type = $this->upsert('transport_services', $row, ServiceType::class, [
                'name' => Normalizer::en(Normalizer::text($row->name)),
                'code' => (string) $row->code,
                'is_default' => (bool) $row->is_default,
                'is_active' => true,
            ], existing: $this->same(ServiceType::class, ['code' => (string) $row->code]));
            $services[(string) $row->code] = $type;
            $running = $row->is_active ? $type : $running;
        }
        $default = collect($services)->first(fn (ServiceType $t): bool => $t->is_default) ?? ServiceType::default();

        // Stops
        $stops = [];
        foreach ($this->rows('stops', fn ($q) => $q->whereNull('deleted_at')) as $row) {
            $stops[$row->id] = $this->upsert('stops', $row, Stop::class, [
                'name' => Normalizer::en(Normalizer::text($row->name)),
                'latitude' => is_numeric($row->latitude) ? (float) $row->latitude : null,
                'longitude' => is_numeric($row->longitude) ? (float) $row->longitude : null,
                'sort_order' => (int) $row->order,
                'is_active' => (string) $row->is_active === '1',
            ])->id;
        }

        // Times, shared by roads in the old system
        $times = $this->legacy()->table('schedules')->whereNull('deleted_at')->get()->keyBy('id');
        $roadStops = $this->legacy()->table('road_stop')->orderBy('id')->get()->groupBy('road_id');
        $roadTimes = $this->legacy()->table('road_schedule')->orderBy('id')->get()->groupBy('road_id');
        $serviceStops = $this->legacy()->table('road_service_stop')->orderBy('id')->get()->groupBy('road_service_id');
        $serviceTimes = $this->legacy()->table('road_service_schedule')->orderBy('id')->get()->groupBy('road_service_id');
        $groups = ['regular_road' => 'regular', 'shuttle_road' => 'shuttle', 'friday_road' => 'friday'];

        // Routes and their default-service variant
        $routes = [];
        $ends = [];   // road id => [start stop, end stop]
        foreach ($this->rows('roads', fn ($q) => $q->whereNull('deleted_at')) as $row) {
            $route = $this->upsert('roads', $row, Route::class, [
                'name' => Normalizer::en(Normalizer::text($row->name)),
                'route_group' => $groups[$row->tag] ?? 'regular',
                'map_url' => preg_match('#^https://\S+$#i', trim((string) $row->map_url)) ? trim((string) $row->map_url) : null,
                'message' => Normalizer::en(trim((string) $row->message) ?: null),
                'sort_order' => (int) $row->order,
                'is_active' => (string) $row->is_active === '1',
            ]);
            $routes[$row->id] = $route;
            $ends[$row->id] = [$row->start_stop_id, $row->end_stop_id];
            if ($default) {
                $this->variant('roads@variant', $row, $route, $default, $row->day_of_week, null,
                    $roadStops->get($row->id, collect()), $roadTimes->get($row->id, collect()), $stops, $times, $row->start_stop_id, $row->end_stop_id);
            }
        }

        // Variants of the other services
        if (Schema::connection('legacy')->hasTable('road_services')) {
            foreach ($this->rows('road_services') as $row) {
                $route = $routes[$row->road_id] ?? null;
                $service = $services[(string) $row->service_type] ?? null;
                if (! $route || ! $service) {
                    $this->report->skipped('road_services', 'road or service not migrated');

                    continue;
                }
                $this->variant('road_services', $row, $route, $service, $row->day_of_week, $row->message,
                    $serviceStops->get($row->id, collect()), $serviceTimes->get($row->id, collect()), $stops, $times, ...($ends[$row->road_id] ?? [null, null]));
            }
        }

        // Settings: running service, title, semester, rules PDF
        $legacy = Schema::connection('legacy')->hasTable('transport_settings') ? $this->legacy()->table('transport_settings')->first() : null;
        $policy = $legacy?->rules_policy_file ? $this->ids->record('files', (string) $legacy->rules_policy_file, MediaItem::class) : null;
        settings()->save('transport', array_filter([
            'running_service_id' => $running?->id,
            'schedule_title' => $legacy?->title ? ['en' => Normalizer::text($legacy->title)] : null,
            'semester' => $legacy?->semester ? ['en' => Normalizer::text($legacy->semester)] : null,
            'policy_file' => $policy?->id,
        ], fn ($v) => $v !== null));
        if ($legacy?->rules_policy_file && ! $policy) {
            $this->report->review('The transport rules PDF waits for the files (S03): run S18T again after S03.');
        }
        if ($running) {
            $this->report->review("Running service now: \"{$running->getTranslation('name', 'en')}\" (as in the old system) — change under Settings › Transport.");
        }
    }

    /** A route's stops and times for one service; replaced on every run. */
    protected function variant(string $table, object $row, Route $route, ServiceType $service, ?string $daysOff, ?string $message, Collection $stopRows, Collection $timeRows, array $stops, Collection $times, mixed $start = null, mixed $end = null): void
    {
        // The old system kept the road's start and end stop on the road (DSC was never in the pivot):
        // the start stop goes first, the end stop last — added when the pivot does not have them
        $legacyIds = $stopRows->pluck('stop_id')->map(fn ($id) => (int) $id)->unique()->values();
        if ($start) {
            $legacyIds = $legacyIds->reject(fn (int $id): bool => $id === (int) $start)->prepend((int) $start)->values();
        }
        if ($end) {
            $legacyIds = $legacyIds->reject(fn (int $id): bool => $id === (int) $end)->push((int) $end)->values();
        }
        $stopIds = $legacyIds->map(fn (int $id) => $stops[$id] ?? null)->filter()->unique()->values()->all();
        if (count($stopIds) < 2) {
            $this->report->skipped($table, 'fewer than two stops');

            return;
        }

        $this->upsert($table, $row, Variant::class, [
            'route_id' => $route->id,
            'service_type_id' => $service->id,
            'days_off' => json_decode((string) $daysOff, true) ?: null,
            'message' => Normalizer::en(trim((string) $message) ?: null),
            'is_active' => true,
        ], after: function (Variant $variant) use ($stopIds, $timeRows, $times): void {
            $variant->syncStops($stopIds);
            $variant->departures()->withTrashed()->forceDelete();
            foreach ($timeRows->values() as $i => $link) {
                $time = $times->get($link->schedule_id);
                if ($time === null) {
                    continue;   // a deleted time
                }
                $variant->departures()->create([
                    'direction' => $time->direction === 'from_home' ? 'to_campus' : 'from_campus',
                    'departs_at' => substr((string) $time->time, 0, 5),
                    'trip_type' => $time->schedule_type === 'shuttle' ? 'shuttle' : 'regular',
                    'days_off' => json_decode((string) $time->day_of_week, true) ?: null,
                    'sort_order' => (int) $time->order ?: $i + 1,
                    'is_active' => (string) $time->is_active === '1',
                ]);
            }
        }, existing: $this->same(Variant::class, ['route_id' => $route->id, 'service_type_id' => $service->id]));
    }
}
