<?php

/*
| Phase 3.8 — M-11 Academic Calendar (DB-03 §12 v1.6, SRS Academic v0.4 FR-CAL-01..09):
| calendars for faculties (none = all), dates as days / ranges / words, type with colour,
| audience filter (AC-CAL-01), /academic-calendar views, .ics (AC-CAL-02), upcoming dates,
| the program tab, the faculty microsite page, import, view count, search and sitemap.
*/

use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Calendar\Blocks\UpcomingDatesBlock;
use Modules\Calendar\Database\Seeders\CalendarDatabaseSeeder;
use Modules\Calendar\Filament\Resources\Calendars\Pages\CreateCalendar;
use Modules\Calendar\Filament\Resources\Calendars\Pages\EditCalendar;
use Modules\Calendar\Filament\Resources\Calendars\RelationManagers\EventsRelationManager;
use Modules\Calendar\Models\Calendar;
use Modules\Calendar\Models\CalendarEvent;
use Modules\Calendar\Support\DateText;
use Modules\Calendar\Support\EventImporter;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\Menu\Support\MenuLinks;
use Modules\Program\Database\Factories\ProgramFactory;
use Modules\Program\Models\Program;
use Platform\Modules\ModulePermissions;
use Platform\Seo\Sitemap;
use Platform\Taxonomy\Vocabularies;
use Platform\Views\ViewCounter;

beforeEach(function () {
    Carbon::setTestNow('2026-02-01 10:00:00');
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    foreach (['Faculty', 'Department', 'Program', 'Calendar'] as $module) {
        app(ModulePermissions::class)->sync($module);
    }
    (new CalendarDatabaseSeeder)->run();

    $term = fn (string $vocabulary, string $name): int => (int) array_search($name, app(Vocabularies::class)->options($vocabulary), true);
    $this->exam = $term(CalendarEvent::TYPE, 'Exam');
    $this->holiday = $term(CalendarEvent::TYPE, 'Holiday');
    $this->ug = $term(CalendarEvent::AUDIENCE, 'Undergraduate');
    $this->grad = $term(CalendarEvent::AUDIENCE, 'Graduate');

    $this->fsit = Faculty::factory()->create(['short_name' => 'FSIT', 'name' => ['en' => 'Science and Information Technology']]);
    $this->fe = Faculty::factory()->create(['short_name' => 'FE', 'name' => ['en' => 'Engineering']]);

    $this->tri = Calendar::factory()->create(['title' => ['en' => 'Spring 2026 (TRI)'], 'session_label' => 'Spring 2026', 'year' => 2026]);
    $this->tri->syncFaculties([$this->fsit->id]);
    $this->bi = Calendar::factory()->create(['title' => ['en' => 'Spring 2026 (BI)'], 'session_label' => 'Spring 2026', 'year' => 2026]);
    $this->bi->syncFaculties([$this->fe->id]);
    $this->old = Calendar::factory()->create(['title' => ['en' => 'Fall 2024'], 'session_label' => 'Fall 2024', 'year' => 2024]);

    $event = fn (Calendar $c, string $title, ?string $from, ?string $to = null, array $extra = [], array $audience = []) => tap(
        CalendarEvent::factory()->create(['calendar_id' => $c->id, 'title' => ['en' => $title], 'starts_on' => $from, 'ends_on' => $to, ...$extra]),
        fn (CalendarEvent $e) => $audience ? $e->syncTerms(CalendarEvent::AUDIENCE, $audience) : null,
    );
    $this->classes = $event($this->tri, 'Classes Start', '2026-01-10', null, ['sort_order' => 1]);
    $this->mid = $event($this->tri, 'Mid-Examination', '2026-02-22', '2026-02-28', ['sort_order' => 2, 'type_term_id' => $this->exam]);
    $this->thesis = $event($this->tri, 'Thesis Defence', '2026-04-05', null, ['sort_order' => 3], [$this->grad]);
    $this->quiz = $event($this->tri, 'First Quiz', null, null, ['sort_order' => 4, 'date_label' => ['en' => '1st week of March']]);
    $this->eid = $event($this->tri, 'Eid-ul-Fitr Vacation', '2026-03-19', '2026-03-23', ['sort_order' => 5, 'type_term_id' => $this->holiday], [$this->ug]);
    $event($this->bi, 'Engineering Classes Start', '2026-01-03', null, ['sort_order' => 1]);
    $event($this->old, 'Fall 2024 Final', '2024-12-10', '2024-12-20');
});

afterEach(fn () => Carbon::setTestNow());

it('keeps dates sound: a range, one day, or words; never an end before the start (FR-CAL-02)', function () {
    expect($this->mid->when())->toBe('22 – 28 February 2026')
        ->and($this->classes->when())->toBe('10 January 2026')
        ->and($this->quiz->when())->toBe('1st week of March');

    $same = CalendarEvent::factory()->create(['calendar_id' => $this->tri->id, 'starts_on' => '2026-05-01', 'ends_on' => '2026-05-01']);
    expect($same->ends_on)->toBeNull();                                                              // one day

    expect(fn () => CalendarEvent::factory()->create(['calendar_id' => $this->tri->id, 'starts_on' => '2026-05-02', 'ends_on' => '2026-05-01']))->toThrow(ValidationException::class)
        ->and(fn () => CalendarEvent::factory()->create(['calendar_id' => $this->tri->id, 'starts_on' => null]))->toThrow(ValidationException::class);
});

it('reads written dates (legacy and import)', function (string $text, ?string $from, ?string $to, string $rest = '') {
    $parsed = DateText::parse($text, 2026);
    if ($from === null) {
        expect($parsed)->toBeNull();

        return;
    }
    expect($parsed['starts_on']->toDateString())->toBe($from)
        ->and($parsed['ends_on']?->toDateString())->toBe($to)
        ->and($parsed['rest'])->toBe($rest);
})->with([
    ['9 May 2026', '2026-05-09', null],
    ['9 May–15 May 2026', '2026-05-09', '2026-05-15'],
    ['30 December 2024-5 January 2025', '2024-12-30', '2025-01-05'],
    ['3 – 10 January 2026', '2026-01-03', '2026-01-10'],
    ['January 9 – 18, 2025', '2025-01-09', '2025-01-18'],
    ['December 29— January 01, 2023', '2022-12-29', '2023-01-01'],
    ['June 26, 2023-July 01, 2023 (Semester Break)', '2023-06-26', '2023-07-01', '(Semester Break)'],
    ['12 July - 15 August', '2026-07-12', '2026-08-15'],
    ['2 March –16 March 2024 (2 Weeks) No. of classes', '2024-03-02', '2024-03-16', '(2 Weeks) No. of classes'],
    ['1st week of March, 2023', null, null],
    ['------', null, null],
]);

it('adds a calendar for some faculties from the admin, then its dates (FR-CAL-01/02)', function () {
    $this->actingAs(superAdmin());

    Livewire::test(CreateCalendar::class)
        ->fillForm(['title' => ['en' => 'Summer 2026 (TRI)'], 'session_label' => 'Summer 2026', 'year' => 2026, 'faculties' => [$this->fsit->id], 'web_address' => 'summer-2026-tri'])
        ->call('create')->assertHasNoFormErrors();
    $summer = Calendar::query()->get()->first(fn (Calendar $c): bool => $c->title === 'Summer 2026 (TRI)');
    expect($summer->facultyIds())->toBe([$this->fsit->id])->and($summer->url())->toBe('/academic-calendar/summer-2026-tri');

    Livewire::test(EventsRelationManager::class, ['ownerRecord' => $summer, 'pageClass' => EditCalendar::class])
        ->callTableAction('create', data: ['title' => ['en' => 'Final Examination'], 'starts_on' => '2026-08-19', 'ends_on' => '2026-08-27', 'type_term_id' => $this->exam, 'audience' => [$this->ug]])
        ->assertHasNoTableActionErrors();
    $final = $summer->events()->sole();
    expect($final->type_term_id)->toBe($this->exam)->and($final->termIdsIn(CalendarEvent::AUDIENCE))->toBe([$this->ug]);

    Livewire::test(EditCalendar::class, ['record' => $summer->id])->fillForm(['faculties' => []])->call('save')->assertHasNoFormErrors();
    expect($summer->fresh()->facultyIds())->toBe([]);                                                 // every faculty
});

it('shows the running calendars first, filters by audience, and lists the others (FR-CAL-04, AC-CAL-01)', function () {
    $this->get('/academic-calendar')->assertOk()
        ->assertSeeInOrder(['Spring 2026 (TRI)', 'Mid-Examination', '22 – 28 February 2026', 'Other calendars', 'Fall 2024'])
        ->assertSee('Spring 2026 (BI)')
        ->assertSee('Thesis Defence')->assertSee('Eid-ul-Fitr Vacation');

    $this->get('/academic-calendar?audience=graduate')->assertOk()
        ->assertSee('Thesis Defence')->assertSee('Mid-Examination')                                   // for graduates, and for everyone
        ->assertDontSee('Eid-ul-Fitr Vacation');                                                      // undergraduates only

    $this->get('/academic-calendar?year=2024')->assertOk()->assertSee('Fall 2024 Final')->assertDontSee('Mid-Examination');
    $this->get('/academic-calendar?faculty='.$this->fe->slug())->assertOk()->assertSee('Engineering Classes Start')->assertDontSee('Mid-Examination');
});

it('shows one calendar as a table, month or timeline, with its colours and the words-only dates (FR-CAL-03/04)', function () {
    $this->get($this->tri->url())->assertOk()
        ->assertSeeInOrder(['Classes Start', 'Mid-Examination', 'Thesis Defence', 'First Quiz', '1st week of March'])
        ->assertSee('background:#D97706', false)                                                      // Exam colour
        ->assertSee('"@type":"EventSeries"', false);

    $this->get($this->tri->url().'?view=month')->assertOk()->assertSeeInOrder(['January 2026', 'February 2026', 'March 2026', 'April 2026', 'Dates to be announced', 'First Quiz']);
    $this->get($this->tri->url().'?view=timeline&audience=graduate')->assertOk()->assertSee('calendar-timeline', false)->assertDontSee('Eid-ul-Fitr Vacation');

    settings()->save('layouts', ['calendar.show' => ['layout' => 'month']]);
    $this->get($this->tri->url())->assertOk()->assertSee('calendar-grid', false);

    $old = $this->tri->url();
    $this->tri->changeSlug('en', 'spring-2026-tri-semester');
    $this->get($old)->assertRedirect('/academic-calendar/spring-2026-tri-semester');                  // FR-GEN-32
    $this->tri->update(['is_active' => false]);
    $this->get('/academic-calendar/spring-2026-tri-semester')->assertNotFound();
});

it('exports dated dates as an .ics file Google Calendar reads on the right days (FR-CAL-06, AC-CAL-02)', function () {
    $response = $this->get($this->tri->url().'.ics')->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
    $ics = $response->getContent();

    expect($ics)->toStartWith("BEGIN:VCALENDAR\r\nVERSION:2.0\r\n")
        ->toContain("SUMMARY:Mid-Examination\r\n")
        ->toContain("DTSTART;VALUE=DATE:20260222\r\nDTEND;VALUE=DATE:20260301\r\n")                    // end is exclusive: 28 Feb shown
        ->toContain("DTSTART;VALUE=DATE:20260110\r\nDTEND;VALUE=DATE:20260111\r\n")                    // one day
        ->toContain('CATEGORIES:Exam')
        ->not->toContain('First Quiz')                                                                // words only
        ->and(substr_count($ics, 'BEGIN:VEVENT'))->toBe(4);

    expect($this->get($this->tri->url().'.ics?audience=graduate')->getContent())->not->toContain('Eid-ul-Fitr Vacation');
});

it('lists upcoming dates on the homepage section and the block, only of homepage calendars (FR-CAL-07)', function () {
    $html = view(UpcomingDatesBlock::view(), ['data' => ['count' => 3], 'locale' => 'en'])->render();
    expect($html)->toContain('Mid-Examination')->toContain('Eid-ul-Fitr Vacation')->toContain('Thesis Defence')
        ->not->toContain('Classes Start')                                                             // already past
        ->not->toContain('First Quiz');                                                               // no exact date

    expect(UpcomingDatesBlock::events(['audience' => $this->grad])->pluck('title')->all())->toBe(['Mid-Examination', 'Thesis Defence']);

    $this->tri->update(['show_on_home' => false]);
    expect(UpcomingDatesBlock::events([])->pluck('title')->all())->toBe([]);
});

it('shows the faculty\'s calendar on its programs and microsite (FR-CAL-09)', function () {
    $cse = Department::factory()->create(['faculty_id' => $this->fsit->id, 'short_name' => 'CSE']);
    $msc = Program::factory()->create(['department_id' => $cse->id, 'name' => ['en' => 'M. Sc. in CSE'], 'level_term_id' => ProgramFactory::level('Graduate')]);
    $eee = Department::factory()->create(['faculty_id' => $this->fe->id, 'short_name' => 'EEE']);
    $bsc = Program::factory()->create(['department_id' => $eee->id, 'name' => ['en' => 'B. Sc. in EEE']]);

    $this->get($msc->url())->assertOk()->assertSee('Academic Calendar')
        ->assertSee('Thesis Defence')->assertDontSee('Eid-ul-Fitr Vacation');                         // graduate level
    $this->get($bsc->url())->assertOk()->assertSee('Engineering Classes Start')->assertDontSee('Mid-Examination');

    $this->get(rtrim($this->fsit->url(), '/').'/academic-calendar')->assertOk()
        ->assertSee('Spring 2026 (TRI)')->assertDontSee('Spring 2026 (BI)')
        ->assertSee('Fall 2024');                                                                     // for every faculty
});

it('imports dates from a file, checking first and reporting lines (FR-CAL-08)', function () {
    $file = UploadedFile::fake()->createWithContent('dates.csv', implode("\n", [
        'Title,Start,End,Date,Type,Audience',
        'Advising & Registration,2026-05-09,2026-05-15,,Registration,',
        'Classes Start,,,12 May 2026,,Undergraduate',
        'Second Quiz,,,2nd week of June,Exam,',
        ',2026-06-01,,,,',
        'Bad Type,2026-06-02,,,Picnic,',
    ]));
    $checked = app(EventImporter::class)->check($this->bi, $file->getRealPath(), 'csv');

    expect($checked['total'])->toBe(5)->and($checked['rows'])->toHaveCount(3)->and($checked['errors'])->toHaveCount(2)
        ->and($checked['errors'][0])->toContain('Line 5')->and($checked['errors'][1])->toContain('"Picnic"');

    app(EventImporter::class)->save($this->bi, $checked['rows']);
    $events = $this->bi->events()->get();
    expect($events->pluck('title')->all())->toBe(['Engineering Classes Start', 'Advising & Registration', 'Classes Start', 'Second Quiz'])
        ->and($events[2]->starts_on->toDateString())->toBe('2026-05-12')
        ->and($events[2]->termIdsIn(CalendarEvent::AUDIENCE))->toBe([$this->ug])
        ->and($events[3]->starts_on)->toBeNull()->and($events[3]->when())->toBe('2nd week of June');
});

it('counts views of a calendar page, not of bots, and sorts by them in the admin (P-17)', function () {
    $this->get($this->tri->url(), ['User-Agent' => 'Mozilla/5.0'])->assertOk();
    $this->get($this->tri->url(), ['User-Agent' => 'Mozilla/5.0'])->assertOk();
    $this->get($this->tri->url(), ['User-Agent' => 'Googlebot/2.1'])->assertOk();
    $this->get($this->tri->url().'.ics', ['User-Agent' => 'Mozilla/5.0'])->assertOk();               // a download is not a page view

    expect(app(ViewCounter::class)->flush())->toBe(1)
        ->and(app(ViewCounter::class)->total($this->tri))->toBe(2)
        ->and(Calendar::query()->mostViewed()->first()->is($this->tri))->toBeTrue();
});

it('offers the page to menus and puts calendars in search and the sitemap', function () {
    expect(app(MenuLinks::class)->modulePagePath('page:calendar.index'))->toBe('/academic-calendar')
        ->and(collect(app(Sitemap::class)->entries())->pluck('loc')->implode(' '))->toContain($this->tri->url())
        ->and($this->tri->toSearchDocument('en'))->toMatchArray(['title' => 'Spring 2026 (TRI)']);
});
