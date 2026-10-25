<?php

/*
| Phase 3.24 — M-33 Facilities (DB-06 v1.3 §4, SRS Services FR-FCL-01..05): each place's
| facilities in their order, by category; a page when there is text, else the link; the homepage
| section with highlights first.
*/

use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Department\Models\Department;
use Modules\Facilities\Filament\Resources\Facilities\Pages\CreateFacility;
use Modules\Facilities\Models\Facility;
use Modules\Facilities\Sections\FacilitiesSection;
use Modules\Faculty\Models\Faculty;
use Modules\Program\Models\Program;
use Platform\Homepage\SectionContext;
use Platform\Modules\ModulePermissions;
use Platform\Relations\RelationEngine;
use Platform\Taxonomy\Vocabularies;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Facilities');
    app(ModulePermissions::class)->syncAttachable('facilities', [Facility::class]);
    $this->engine = app(RelationEngine::class);
    $this->make = function (string $title, array $attributes = [], array $places = [], bool $main = true): Facility {
        $f = Facility::factory()->create(['title' => ['en' => $title], ...$attributes]);
        $this->engine->sync($f, $places, mainSite: $main, user: null);

        return $f;
    };
});

it('lists the place\'s facilities in their order, by category, with a page or a link (FR-FCL-02/03)', function () {
    Vocabularies::ensureTerms(Facility::CATEGORY, [['name' => ['en' => 'Health'], 'slug' => 'health'], ['name' => ['en' => 'Study'], 'slug' => 'study']]);
    $health = (int) array_search('Health', app(Vocabularies::class)->options(Facility::CATEGORY), true);
    $medical = ($this->make)('Medical Centre', ['body' => ['en' => '<p>Open 24 hours.</p>']]);
    $medical->syncTerms(Facility::CATEGORY, [$health]);
    ($this->make)('Library', ['external_url' => 'https://library.example.edu', 'open_in_new_tab' => true]);
    ($this->make)('Closed Pool', ['is_active' => false]);
    $cse = Department::factory()->create(['faculty_id' => Faculty::factory()->create()->id, 'short_name' => 'CSE']);
    ($this->make)('CSE Robotics Lab Room', [], ['department.department:'.$cse->id], main: false);

    $this->get('/facilities')->assertOk()->assertSeeInOrder(['Medical Centre', 'Library'])
        ->assertSee('href="https://library.example.edu"', false)->assertSee('target="_blank"', false)->assertDontSee('Closed Pool')->assertDontSee('CSE Robotics');
    $this->get('/facilities?category=health')->assertOk()->assertSee('Medical Centre')->assertDontSee('Library');
    $this->get('/facilities')->assertSeeInOrder(['Campus Facilities', 'facility-card', 'Medical Centre'], false);   // the default theme: DIU's picture cards
    $this->get($medical->url())->assertOk()->assertSee('Open 24 hours.');
    $this->get('/facilities/'.Facility::query()->get()->first(fn ($f) => $f->title === 'Library')->slug())->assertNotFound();   // no text: no page
    $this->get(rtrim($cse->url(), '/').'/facilities')->assertOk()->assertSee('CSE Robotics Lab Room');
});

it('fills "Campus Facilities" with highlights first (FR-FCL-04) and keeps links valid', function () {
    $a = ($this->make)('Cafeteria');
    $b = ($this->make)('Gym');
    $this->engine->sync($b, [], mainSite: true, highlights: ['site:0' => ['order' => 1]], user: null);

    expect((new FacilitiesSection)->items(new SectionContext('site:0', ['count' => 5], 'en'))->pluck('title')->all())->toBe(['Gym', 'Cafeteria']);
    expect(fn () => Facility::factory()->create(['external_url' => 'javascript:alert(1)']))->toThrow(ValidationException::class);

    $this->actingAs(superAdmin());
    Livewire::test(CreateFacility::class)->fillForm(['title' => ['en' => 'Hostel'], 'show_in' => [], 'main_site' => true])->call('create')->assertHasNoFormErrors();
    expect(Facility::query()->where('title->en', 'Hostel')->exists())->toBeTrue();
});

it('shows a program\'s facilities on its page as a tab; none, no tab (FR-FCL-05)', function () {
    $program = Program::factory()->create();
    $other = Program::factory()->create();
    ($this->make)('MBA Lounge', ['summary' => ['en' => 'Quiet study for MBA students.']], [$program->getMorphClass().':'.$program->id], main: false);

    $this->get($program->url())->assertOk()->assertSee('href="#facilities-program"', false)->assertSee('MBA Lounge')->assertSee('Quiet study for MBA students.');
    $this->get($other->url())->assertOk()->assertDontSee('href="#facilities-program"', false)->assertDontSee('MBA Lounge');
    $this->get('/facilities')->assertDontSee('MBA Lounge');
});
