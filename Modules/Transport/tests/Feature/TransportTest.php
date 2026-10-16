<?php

/*
| Phase 3.23 — M-31 Transport (DB-06 v1.1 §2, owner 2026-10-17): the live transport system stays
| the owner of the data; the website reads its public API (fixture: a cut of the real answer),
| caches it, keeps the last good answer for when the API is down, and shows /transport with the
| routes by service, times both ways, days off, notes, map and a stop search.
*/

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Transport\Sections\TransportFinderSection;
use Modules\Transport\Settings\TransportSettings;
use Modules\Transport\Support\TransportApi;
use Platform\Homepage\SectionContext;

beforeEach(function () {
    Cache::forget(TransportApi::CACHE);
    Cache::forget(TransportApi::LAST_GOOD);
    $this->fixture = json_decode((string) file_get_contents(__DIR__.'/../fixtures/transport-v2.json'), true);
});

it('reads the live API into routes by service, with stops and times both ways', function () {
    Http::fake([TransportSettings::DEFAULT_URL => Http::response($this->fixture)]);

    $t = app(TransportApi::class)->timetable();
    expect($t['ok'])->toBeTrue()->and($t['stale'])->toBeFalse()
        ->and($t['semester'])->toBe('Semester : Summer-2026')
        ->and(array_keys($t['groups']))->toBe(['regular_road', 'shuttle_road'])                                  // empty Friday list left out
        ->and($t['groups']['regular_road'][0])->toMatchArray(['name' => 'Dhanmondi <> DSC', 'days_off' => ['Friday']])
        ->and(array_column($t['groups']['regular_road'][0]['to_campus'], 'time'))->toBe(['07:00', '10:00'])
        ->and(array_column($t['groups']['regular_road'][0]['from_campus'], 'time'))->toBe(['13:30', '16:20', '18:10'])
        ->and($t['groups']['regular_road'][0]['stops'][0])->toBe('Dhanmondi - Sobhanbag Mosque')
        ->and($t['groups']['regular_road'][0]['notes'][0])->toStartWith('Bus services on each route');      // "Note:" and numbers removed

    app(TransportApi::class)->timetable();
    Http::assertSentCount(1);                                                                                       // cached
});

it('shows /transport with tabs, times, notes, the map on request and the stop search', function () {
    Http::fake([TransportSettings::DEFAULT_URL => Http::response($this->fixture)]);

    $this->get('/transport')->assertOk()
        ->assertSeeInOrder(['50+ dedicated buses', 'Get Your DIU Transport Pass', 'How to Get Your Pass', 'Why It Matters'])   // Settings › Transport defaults
        ->assertSee('Regular Service Schedule @ DSC')->assertSee('Special Transport Schedule for Exam-2026')->assertSee('View Rules &amp; Policy', false)
        ->assertSeeInOrder(['Regular', '(2)', 'Shuttle', '(1)'])
        ->assertSeeInOrder(['Dhanmondi &lt;&gt; DSC', 'Departure from Dhanmondi - Sobhanbag Mosque', '7:00 AM', '10:00 AM', 'Departure from Daffodil Smart City-DSC', '1:30 PM'], false)
        ->assertSeeInOrder(['Transport Fees', 'Tri-Semester', 'Bi-Semester', 'Dhanmondi', '3000/=tk', '4500/=tk'])
        ->assertSee('No bus on: Friday')
        ->assertSee('Shuttle : Technical Bus Stand')
        ->assertSee('Find your bus')->assertSee('<option value="Azampur Bus Stop">', false)
        ->assertSee('x-if="map"', false);
});

it('keeps showing the last good schedule when the API is down, and says so', function () {
    Http::fake([TransportSettings::DEFAULT_URL => Http::sequence()->push($this->fixture)->push('', 500)]);
    app(TransportApi::class)->timetable();                                                                          // a good answer
    Cache::forget(TransportApi::CACHE);                                                                             // its time is over

    $this->get('/transport')->assertOk()->assertSee('Dhanmondi &lt;&gt; DSC', false)->assertSee('did not answer just now');

    Cache::forget(TransportApi::CACHE);
    Cache::forget(TransportApi::LAST_GOOD);
    Http::fake([TransportSettings::DEFAULT_URL => Http::response(['status' => false])]);
    $this->get('/transport')->assertOk()->assertSee('cannot be shown right now');
});

it('offers the stop search on the homepage and only on the main site', function () {
    Http::fake([TransportSettings::DEFAULT_URL => Http::response($this->fixture)]);

    $items = (new TransportFinderSection)->items(new SectionContext('site:0', [], 'en'));
    expect($items['stops'])->toContain('Azampur Bus Stop')->toContain('Technical');

    $html = view('transport::sections.finder', ['items' => $items, 'section' => null, 'settings' => []])->render();
    expect($html)->toContain('Find your bus')->toContain('/transport?service=');
});
