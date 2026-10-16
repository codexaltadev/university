<?php

/*
| Phase 3.14 — M-19 Events (DB-05 §4 v1.3, SRS Content FR-EVT-01..10): dates (start required, end
| after it), upcoming / ongoing / past from the dates (AC-EVT-01), places and the main site,
| tabs and layouts with a month calendar, registration closing, speakers, .ics and Google
| Calendar, the two homepage sections, JSON-LD, search and sitemap.
*/

use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Modules\Administration\Models\AdministrationCategory;
use Modules\Administration\Models\Member;
use Modules\Department\Models\Department;
use Modules\Events\Models\Event;
use Modules\Events\Sections\PastEventsSection;
use Modules\Events\Sections\UpcomingEventsSection;
use Modules\Faculty\Models\Faculty;
use Platform\Assignments\AssignmentEngine;
use Platform\Assignments\RoleCatalog;
use Platform\Homepage\SectionContext;
use Platform\Modules\ModulePermissions;
use Platform\Relations\RelationEngine;
use Platform\Seo\Sitemap;

beforeEach(function () {
    Carbon::setTestNow('2026-11-10 09:00:00');
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    foreach (['Faculty', 'Department', 'Events'] as $module) {
        app(ModulePermissions::class)->sync($module);
    }
    app(RoleCatalog::class)->sync();
    $this->engine = app(RelationEngine::class);
    $this->cse = Department::factory()->create(['faculty_id' => Faculty::factory()->create()->id, 'short_name' => 'CSE']);

    $main = fn (Event $e) => tap($e, fn () => $this->engine->sync($e, [], mainSite: true, user: null));
    $this->hackathon = $main(Event::factory()->create(['title' => ['en' => 'Spring Hackathon'], 'starts_at' => '2026-11-20 10:00', 'ends_at' => '2026-11-21 18:00',
        'registration_url' => 'https://example.edu/register', 'registration_ends_at' => '2026-11-15 23:59', 'venue' => ['en' => 'Auditorium']]));
    $this->fair = $main(Event::factory()->create(['title' => ['en' => 'Career Fair'], 'starts_at' => '2026-11-10 00:00', 'ends_at' => null, 'is_all_day' => true]));
    $this->convocation = $main(Event::factory()->create(['title' => ['en' => '12th Convocation'], 'starts_at' => '2026-10-01 10:00', 'ends_at' => '2026-10-01 14:00']));
    $this->seminar = Event::factory()->create(['title' => ['en' => 'CSE AI Seminar'], 'starts_at' => '2026-11-25 14:00', 'ends_at' => '2026-11-25 16:00']);
    $this->engine->sync($this->seminar, ['department.department:'.$this->cse->id], mainSite: false, user: null);
});

afterEach(fn () => Carbon::setTestNow());

it('needs a start, keeps the end after it, and knows upcoming / ongoing / past (FR-EVT-01/02)', function () {
    expect(fn () => Event::factory()->create(['starts_at' => '2026-12-02 10:00', 'ends_at' => '2026-12-01 10:00']))->toThrow(ValidationException::class);

    expect($this->hackathon->state())->toBe('upcoming')
        ->and($this->fair->state())->toBe('ongoing')                                                 // all day, today
        ->and($this->convocation->state())->toBe('past')
        ->and($this->hackathon->when())->toBe('20 November 2026, 10:00 – 21 November 2026, 18:00')
        ->and($this->fair->when())->toBe('10 November 2026');

    Carbon::setTestNow('2026-11-11 00:01');
    expect($this->fair->fresh()->state())->toBe('past');
});

it('moves an event to Past once it is over, on the website and the homepage (AC-EVT-01, FR-EVT-08)', function () {
    $this->get('/events')->assertOk()->assertSeeInOrder(['Career Fair', 'Spring Hackathon'])->assertDontSee('12th Convocation')->assertDontSee('CSE AI Seminar');
    $this->get('/events?tab=past')->assertOk()->assertSee('12th Convocation')->assertDontSee('Spring Hackathon');

    $upcoming = fn () => (new UpcomingEventsSection)->items(new SectionContext('site:0', ['count' => 5], 'en'))->pluck('title')->all();
    expect($upcoming())->toBe(['Career Fair', 'Spring Hackathon']);

    Carbon::setTestNow('2026-11-22 09:00');
    expect($upcoming())->toBe([]);
    $this->get('/events?tab=past')->assertSeeInOrder(['Spring Hackathon', 'Career Fair', '12th Convocation']);

    $past = (new PastEventsSection)->items(new SectionContext('site:0', ['count' => 2], 'en'));
    expect($past->pluck('title')->all())->toBe(['Spring Hackathon', 'Career Fair']);              // no highlight: the latest past
    $this->engine->sync($this->convocation, [], mainSite: true, highlights: ['site:0' => ['order' => 1]], user: null);
    expect((new PastEventsSection)->items(new SectionContext('site:0', ['count' => 2], 'en'))->pluck('title')->all())->toBe(['12th Convocation']);
});

it('shows an entity\'s event only on its microsite (AC-NEWS-01 rule for events)', function () {
    $base = rtrim($this->cse->url(), '/');
    $this->get($base.'/events')->assertOk()->assertSee('CSE AI Seminar')->assertDontSee('Spring Hackathon');
    $this->get($base.'/events/'.$this->seminar->slug())->assertOk();
    $this->get('/events/'.$this->seminar->slug())->assertNotFound();
    expect($this->seminar->url())->toBe($base.'/events/'.$this->seminar->slug());
});

it('draws the month calendar and the other layouts (FR-EVT-03)', function () {
    $this->get('/events?view=calendar&month=2026-11')->assertOk()
        ->assertSee('November 2026')->assertSee('events-calendar', false)
        ->assertSee('Spring Hackathon')->assertSee('Career Fair')->assertDontSee('12th Convocation');
    $this->get('/events?view=cards')->assertOk()->assertSee('Spring Hackathon');
    $this->get('/events?view=timeline&tab=past')->assertOk()->assertSee('12th Convocation');
});

it('closes registration after its time and offers .ics and Google Calendar (FR-EVT-06/07/09)', function () {
    $this->get($this->hackathon->url())->assertOk()
        ->assertSee('https://example.edu/register', false)->assertSee('Registration until')
        ->assertSee('calendar.google.com/calendar/render', false)
        ->assertSee('"@type":"Event"', false)->assertSee('"startDate"', false);

    $ics = $this->get($this->hackathon->url().'.ics')->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8')->getContent();
    expect($ics)->toContain('DTSTART:'.Carbon::parse('2026-11-20 10:00')->utc()->format('Ymd\THis\Z')."\r\n")   // timed: in UTC
        ->toContain('SUMMARY:Spring Hackathon')->toContain('LOCATION:Auditorium');
    expect($this->get($this->fair->url().'.ics')->getContent())->toContain("DTSTART;VALUE=DATE:20261110\r\nDTEND;VALUE=DATE:20261111\r\n");

    Carbon::setTestNow('2026-11-16 09:00');
    $this->get($this->hackathon->url())->assertOk()->assertSee('Registration closed')->assertDontSee('https://example.edu/register', false);
});

it('lists speakers from any directory (FR-EVT-04)', function () {
    $category = AdministrationCategory::query()->create(['name' => ['en' => 'Faculty'], 'group' => 'officer', 'is_public' => true, 'is_active' => true]);
    $speaker = Member::factory()->in($category)->create(['name' => ['en' => 'Dr. Sultana'], 'designation' => ['en' => 'Professor']]);
    app(AssignmentEngine::class)->assign($speaker, $this->hackathon, 'speaker', ['role_label' => ['en' => 'Keynote speaker']]);

    $this->get($this->hackathon->url())->assertOk()->assertSeeInOrder(['Speakers', 'Dr. Sultana', 'Keynote speaker', 'Professor']);
})->skip(fn () => ! class_exists(Member::class), 'Administration module not present');

it('puts events in search and the sitemap', function () {
    expect(collect(app(Sitemap::class)->entries())->pluck('loc')->implode(' '))->toContain($this->hackathon->url())->not->toContain($this->seminar->slug().'"')
        ->and($this->hackathon->toSearchDocument('en'))->toMatchArray(['title' => 'Spring Hackathon']);
});
