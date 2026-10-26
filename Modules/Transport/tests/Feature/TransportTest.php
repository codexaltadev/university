<?php

/*
| Phase 3.23 — M-31 Transport (DB-06 v1.2 §2, owner 2026-10-17: the old transport system goes, the
| data lives here): service types, stops, routes; per service a route's ordered stops, days off and
| times (variants); the running service from Settings with the default as fallback; /transport,
| the stop search, and the old system's public API with the same JSON.
*/

use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Transport\Database\Seeders\TransportDatabaseSeeder;
use Modules\Transport\Filament\Resources\Routes\Pages\EditRoute;
use Modules\Transport\Filament\Resources\Routes\RelationManagers\VariantsRelationManager;
use Modules\Transport\Models\Route;
use Modules\Transport\Models\ServiceType;
use Modules\Transport\Models\Stop;
use Modules\Transport\Models\Variant;
use Modules\Transport\Sections\TransportFinderSection;
use Modules\Transport\Support\Timetable;
use Platform\Homepage\SectionContext;
use Platform\Modules\ModulePermissions;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Transport');
    (new TransportDatabaseSeeder)->run();
    $this->regular = ServiceType::query()->where('code', 'regular_service')->sole();
    $this->exam = ServiceType::query()->create(['name' => ['en' => 'Final Examination Schedule'], 'code' => 'final_exam_service', 'is_active' => true]);
    $this->stop = fn (string $name): Stop => Stop::query()->create(['name' => ['en' => $name], 'is_active' => true]);

    // Dhanmondi <> DSC: regular service, and an exam variant
    [$this->sobhanbag, $asad, $this->dsc] = [($this->stop)('Dhanmondi - Sobhanbag Mosque'), ($this->stop)('Asad Gate'), ($this->stop)('Daffodil Smart City-DSC')];
    $this->dhanmondi = Route::factory()->create(['name' => ['en' => 'Dhanmondi <> DSC'], 'map_url' => 'https://www.google.com/maps/d/embed?mid=abc', 'message' => ['en' => "Note:\n1. Be there 10 minutes early.\n2. The bus will not wait."]]);
    $this->variant = function (Route $route, ServiceType $service, array $stops, array $times, array $daysOff = ['Friday']): Variant {
        $v = Variant::query()->create(['route_id' => $route->id, 'service_type_id' => $service->id, 'days_off' => $daysOff, 'is_active' => true]);
        $v->syncStops(array_map(fn (Stop $s) => $s->id, $stops));
        foreach ($times as [$direction, $time, $type]) {
            $v->departures()->create(['direction' => $direction, 'departs_at' => $time, 'trip_type' => $type, 'is_active' => true]);
        }

        return $v;
    };
    ($this->variant)($this->dhanmondi, $this->regular, [$this->sobhanbag, $asad, $this->dsc], [['to_campus', '07:00', 'regular'], ['to_campus', '10:00', 'regular'], ['from_campus', '13:30', 'regular'], ['from_campus', '16:20', 'regular']]);
    ($this->variant)($this->dhanmondi, $this->exam, [$this->sobhanbag, $this->dsc], [['to_campus', '08:00', 'regular'], ['from_campus', '14:00', 'regular']]);

    $mirpur = ($this->stop)('Mirpur-1, Sony Cinema Hall');
    $shuttle = Route::factory()->create(['name' => ['en' => 'Shuttle: Mirpur-1 <> DSC'], 'route_group' => 'shuttle']);
    ($this->variant)($shuttle, $this->regular, [$mirpur, $this->dsc], [['to_campus', '08:30', 'shuttle'], ['from_campus', '11:15', 'shuttle']], ['Saturday', 'Friday']);

    settings()->save('transport', ['schedule_title' => ['en' => 'Special Transport Schedule for Exam-2026'], 'semester' => ['en' => 'Semester : Summer-2026']]);
});

it('shows the running service: each route\'s own variant, else the default service\'s (DB-06 v1.2 §2)', function () {
    $t = app(Timetable::class)->website();
    expect(array_keys($t['groups']))->toBe(['regular_road', 'shuttle_road'])
        ->and($t['service'])->toBe('Regular Service')
        ->and(array_column($t['groups']['regular_road'][0]['to_campus'], 'time'))->toBe(['07:00', '10:00'])
        ->and($t['groups']['regular_road'][0])->toMatchArray(['from' => 'Dhanmondi - Sobhanbag Mosque', 'to' => 'Daffodil Smart City-DSC', 'days_off' => ['Friday']])
        ->and($t['groups']['regular_road'][0]['notes'])->toBe(['Be there 10 minutes early.', 'The bus will not wait.']);

    settings()->save('transport', ['running_service_id' => $this->exam->id]);
    $t = app(Timetable::class)->website();
    expect($t['service'])->toBe('Final Examination Schedule')
        ->and(array_column($t['groups']['regular_road'][0]['to_campus'], 'time'))->toBe(['08:00'])                  // the exam variant
        ->and(array_column($t['groups']['shuttle_road'][0]['to_campus'], 'time'))->toBe(['08:30']);                // no exam variant: the regular one
});

it('shows /transport with the schedule, tabs, departures, notes and the stop search', function () {
    $this->get('/transport')->assertOk()
        ->assertSee('Regular Service Schedule @ DSC')->assertSee('Special Transport Schedule for Exam-2026')->assertSee('Semester : Summer-2026')
        ->assertSeeInOrder(['Regular', '(1)', 'Shuttle', '(1)'])
        ->assertSeeInOrder(['Dhanmondi &lt;&gt; DSC', 'No bus on: Friday', 'Departure from Dhanmondi - Sobhanbag Mosque', '7:00 AM', '10:00 AM', 'Departure from Daffodil Smart City-DSC', '1:30 PM', '4:20 PM'], false)
        ->assertSee('Be there 10 minutes early.')
        ->assertSee('<option value="Asad Gate">', false)
        ->assertSeeInOrder(['Transport Fees', 'Tri-Semester', '3000/=tk'])
        ->assertSeeInOrder(['Transport Service', 'Get Your DIU Transport Pass', 'How to Get Your Pass', 'Why It Matters', 'Find your bus', 'Route:', 'Dhanmondi &lt;&gt; DSC'], false);   // the default theme: DIU's /transport

    $items = (new TransportFinderSection)->items(new SectionContext('site:0', [], 'en'));
    expect($items['stops'])->toContain('Mirpur-1, Sony Cinema Hall');
});

it('gives the old frontend the same JSON at /api/v2/public/transport', function () {
    $response = $this->getJson('/api/v2/public/transport')->assertOk()->assertHeader('Access-Control-Allow-Origin', '*');

    $response->assertJson([
        'status' => true, 'title' => 'Special Transport Schedule for Exam-2026', 'semester' => 'Semester : Summer-2026',
        'message' => 'List of transport roads v2', 'active_service_name' => 'Regular Service', 'active_service_code' => 'regular_service', 'total_roads' => 2,
    ]);
    $road = $response->json('data.regular_road.0');
    expect($road)->toMatchArray(['name' => 'Dhanmondi <> DSC', 'start_stop' => 'Dhanmondi - Sobhanbag Mosque', 'end_stop' => 'Daffodil Smart City-DSC', 'tag' => 'regular_road', 'day_off' => ['Friday'], 'service_code' => 'regular_service', 'map_url' => 'https://www.google.com/maps/d/embed?mid=abc'])
        ->and(array_column($road['stops'], 'name'))->toBe(['Dhanmondi - Sobhanbag Mosque', 'Asad Gate', 'Daffodil Smart City-DSC'])
        ->and($road['schedules']['from_home'][0])->toMatchArray(['time' => '07:00:00', 'direction' => 'from_home', 'schedule_type' => 'regular', 'order' => 1])
        ->and(array_column($road['schedules']['from_campus'], 'time'))->toBe(['13:30:00', '16:20:00'])
        ->and($response->json('data.friday_road'))->toBe([])
        ->and($response->json('data.shuttle_road.0.schedules.from_home.0.schedule_type'))->toBe('shuttle');
});

it('keeps routes, variants and times valid (BRL-TRN-01)', function () {
    $v = Variant::query()->where('route_id', $this->dhanmondi->id)->where('service_type_id', $this->regular->id)->sole();
    expect(fn () => $v->syncStops([$this->dsc->id]))->toThrow(ValidationException::class)
        ->and(fn () => Variant::query()->create(['route_id' => $this->dhanmondi->id, 'service_type_id' => $this->regular->id]))->toThrow(ValidationException::class)
        ->and(fn () => $v->departures()->create(['direction' => 'to_campus', 'departs_at' => '25:00']))->toThrow(ValidationException::class)
        ->and(fn () => Route::factory()->create(['route_group' => 'night']))->toThrow(ValidationException::class)
        ->and(fn () => Route::factory()->create(['map_url' => 'javascript:alert(1)']))->toThrow(ValidationException::class);

    $this->exam->update(['is_default' => true]);                                                                   // one default only
    expect($this->regular->fresh()->is_default)->toBeFalse();
});

it('enters a route\'s stops and times for a service in the admin, and copies them to another service', function () {
    $this->actingAs(superAdmin());
    $route = Route::factory()->create(['name' => ['en' => 'Savar <> DSC']]);
    $savar = ($this->stop)('Savar Model Mosque');

    Livewire::test(VariantsRelationManager::class, ['ownerRecord' => $route, 'pageClass' => EditRoute::class])
        ->callTableAction('create', data: [
            'service_type_id' => $this->regular->id, 'days_off' => ['Saturday', 'Friday'], 'is_active' => true,
            'stop_rows' => [['stop_id' => $savar->id], ['stop_id' => $this->dsc->id]],
            'departure_rows' => [
                ['direction' => 'to_campus', 'departs_at' => '07:00', 'trip_type' => 'regular', 'days_off' => []],
                ['direction' => 'from_campus', 'departs_at' => '16:20', 'trip_type' => 'regular', 'days_off' => []],
            ],
        ])->assertHasNoTableActionErrors();

    $variant = $route->variants()->sole();
    expect($variant->stops->pluck('id')->all())->toBe([$savar->id, $this->dsc->id])
        ->and($variant->departures->map->time()->all())->toBe(['07:00', '16:20'])
        ->and($variant->days_off)->toBe(['Saturday', 'Friday']);

    Livewire::test(VariantsRelationManager::class, ['ownerRecord' => $route, 'pageClass' => EditRoute::class])
        ->callTableAction('copy', $variant, data: ['service_type_id' => $this->exam->id]);
    expect($route->variants()->where('service_type_id', $this->exam->id)->sole()->departures()->count())->toBe(2);
});
