<?php

/*
| Phase 3.22 — M-30 Announcement Widgets (DB-05 §15 v1.11, SRS Content FR-ANW-01..07): bar,
| countdown, ticker and popup in the theme's layout slots (kernel slot → SiteData "slot:{name}");
| places ("Show in", main site), pages (homepage / all / chosen module pages), from / until; one
| popup by priority; latest notices in the ticker; countdown end.
*/

use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Announcement\Filament\Resources\Widgets\Pages\CreateWidget;
use Modules\Announcement\Filament\Resources\Widgets\Pages\ListWidgets;
use Modules\Announcement\Models\Widget;
use Modules\Announcement\Settings\AnnouncementSettings;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\Notice\Models\Notice;
use Platform\Modules\ModulePermissions;
use Platform\Relations\RelationEngine;

beforeEach(function () {
    Carbon::setTestNow('2026-10-17 10:00:00');
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Announcement');
    app(ModulePermissions::class)->syncAttachable('announcement', [Widget::class]);
    $this->engine = app(RelationEngine::class);
    settings()->save('announcement', ['popup' => true, 'ticker' => true, 'bar' => true, 'countdown' => true]);   // all off at first

    $this->make = function (string $type, string $title, array $attributes = [], array $places = [], bool $main = true): Widget {
        $w = Widget::factory()->create(['widget_type' => $type, 'title' => ['en' => $title], ...$attributes]);
        $this->engine->sync($w, $places, mainSite: $main, user: null);

        return $w;
    };
});

afterEach(fn () => Carbon::setTestNow());

it('shows a bar on the pages it is for: the homepage, all pages or chosen module pages (FR-ANW-02)', function () {
    ($this->make)('bar', 'Admission open for Spring 2027');
    ($this->make)('bar', 'Campus closed on Friday', ['pages' => 'all']);
    ($this->make)('bar', 'Results are out', ['pages' => 'selected', 'route_keys' => ['notice.index']]);

    $this->get('/')->assertOk()->assertSee('Admission open for Spring 2027')->assertSee('Campus closed on Friday')->assertDontSee('Results are out');
    $this->get('/notices')->assertOk()->assertDontSee('Admission open for Spring 2027')->assertSee('Campus closed on Friday')->assertSee('Results are out');
    $this->get('/links')->assertSee('Campus closed on Friday')->assertDontSee('Results are out');

    ($this->make)('bar', 'Fee waiver week', ['pages' => 'selected', 'route_keys' => ['/links']]);           // an address of the site
    $this->get('/links')->assertSee('Fee waiver week');
    $this->get('/notices')->assertDontSee('Fee waiver week');
});

it('keeps each place\'s widgets on its own site, from / until (FR-ANW-02/03)', function () {
    $cse = Department::factory()->create(['faculty_id' => Faculty::factory()->create()->id, 'short_name' => 'CSE']);
    ($this->make)('bar', 'CSE seminar today', [], ['department.department:'.$cse->id], main: false);
    ($this->make)('bar', 'Main site only');
    ($this->make)('bar', 'Not yet', ['active_at' => '2026-10-20 00:00']);
    ($this->make)('bar', 'Over', ['active_at' => '2026-10-01 00:00', 'expires_at' => '2026-10-10 00:00']);

    $this->get($cse->url())->assertOk()->assertSee('CSE seminar today')->assertDontSee('Main site only');
    $this->get('/')->assertOk()->assertSee('Main site only')->assertDontSee('CSE seminar today')->assertDontSee('Not yet')->assertDontSee('Over');

    expect(fn () => Widget::factory()->create(['active_at' => '2026-11-01', 'expires_at' => '2026-10-01']))->toThrow(ValidationException::class)
        ->and(fn () => Widget::factory()->create(['pages' => 'selected', 'route_keys' => []]))->toThrow(ValidationException::class)
        ->and(fn () => Widget::factory()->create(['widget_type' => 'countdown']))->toThrow(ValidationException::class)
        ->and(fn () => Widget::factory()->create(['link_url' => 'javascript:alert(1)']))->toThrow(ValidationException::class);
});

it('shows one popup only, the highest priority, as an accessible dialog (FR-ANW-04/05)', function () {
    ($this->make)('popup', 'Eid Mubarak', ['priority' => 1, 'frequency' => 'once']);
    ($this->make)('popup', 'Admission fair', ['priority' => 5, 'body' => ['en' => '<p>Visit us on 1 November</p>']]);

    $this->get('/')->assertOk()->assertSee('Admission fair')->assertSee('Visit us on 1 November')->assertDontSee('Eid Mubarak')
        ->assertSee('role="dialog"', false)->assertSee('aria-modal="true"', false)->assertSee('x-trap', false)
        ->assertSee('keydown.escape', false)->assertSee("frequency: 'daily'", false);
});

it('runs the ticker lines with the latest notices of the board (FR-ANW-06)', function () {
    ($this->make)('ticker', 'Ticker', ['body' => ['en' => 'Welcome to the new website'], 'include_latest_notices' => true]);
    $notice = Notice::factory()->create(['title' => ['en' => 'Mid-term exam routine'], 'active_at' => '2026-10-16 09:00']);
    $this->engine->sync($notice, [], mainSite: true, user: null);

    $this->get('/')->assertOk()->assertSeeInOrder(['Welcome to the new website', 'Mid-term exam routine'])
        ->assertSee('announcement-ticker__track', false)->assertSee('aria-hidden="true"', false);   // shown twice for an endless run, the copy hidden

    settings()->saveFor(new AnnouncementSettings, ['ticker_label' => ['en' => 'Breaking']]);
    $this->get('/')->assertSee('Breaking');
});

it('counts down, then hides or shows the message (FR-ANW-07)', function () {
    ($this->make)('countdown', 'Convocation 2026', ['target_at' => '2026-12-01 10:00']);
    ($this->make)('countdown', 'Fair over', ['target_at' => '2026-10-01 10:00']);
    ($this->make)('countdown', 'Admission test', ['target_at' => '2026-10-02 10:00', 'after_end' => 'message', 'after_end_text' => ['en' => 'The test has started']]);

    $this->get('/')->assertOk()->assertSee('Convocation 2026')->assertSee('role="timer"', false)
        ->assertDontSee('Fair over')->assertSee('The test has started');
});

it('is made in the admin with the type\'s own fields', function () {
    $this->actingAs(superAdmin());
    Livewire::test(CreateWidget::class)
        ->fillForm(['widget_type' => 'countdown'])->assertFormFieldIsVisible('target_at')->assertFormFieldIsHidden('frequency')
        ->fillForm(['widget_type' => 'popup'])->assertFormFieldIsVisible('frequency')->assertFormFieldIsHidden('target_at')
        ->fillForm(['widget_type' => 'bar', 'title' => ['en' => 'Library open late'], 'pages' => 'all', 'color_token' => 'color-accent', 'show_in' => [], 'main_site' => true])
        ->call('create')->assertHasNoFormErrors();

    expect(Widget::query()->sole()->pages)->toBe('all');
    $this->get('/notices')->assertSee('Library open late')->assertSee('var(--color-accent)', false);
});

it('shows each kind only while it is on in Settings › Announcements — all off at first (owner 2026-10-25)', function () {
    settings()->save('announcement', ['popup' => false, 'ticker' => false, 'bar' => false, 'countdown' => false]);
    ($this->make)('bar', 'Library open late');
    ($this->make)('ticker', 'Ticker', ['body' => ['en' => 'Welcome to the new website']]);
    ($this->make)('countdown', 'Convocation 2026', ['target_at' => '2026-12-01 10:00']);
    ($this->make)('popup', 'Eid Mubarak', ['link_url' => '/admission']);

    $this->get('/')->assertOk()->assertDontSee('Library open late')->assertDontSee('Welcome to the new website')
        ->assertDontSee('Convocation 2026')->assertDontSee('Eid Mubarak');

    $this->actingAs(superAdmin());
    Livewire::test(ListWidgets::class)->assertSee('Turned off in Settings › Announcements, so not shown: Popup, News ticker, Countdown, Announcement bar.');
    Livewire::test(CreateWidget::class)->fillForm(['widget_type' => 'popup'])->assertSee('Popup is turned off in Settings › Announcements');

    settings()->save('announcement', ['popup' => true, 'bar' => true]);
    $this->get('/')->assertOk()->assertSee('Library open late')->assertSee('Eid Mubarak')->assertDontSee('Welcome to the new website')->assertDontSee('Convocation 2026')
        ->assertSeeInOrder(['Eid Mubarak', 'Maybe Later', 'Explore Now'])                                // DIU's greeting popup
        ->assertSee('from-secondary via-primary to-secondary', false);                                    // DIU's navy → blue
});
