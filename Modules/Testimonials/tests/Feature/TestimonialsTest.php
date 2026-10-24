<?php

/*
| Phase 3.16 — M-21 Testimonials (DB-05 §6 v1.5, SRS Content FR-TEST-01..04): one person from a
| directory or added by hand with their own title, quote per language, type / rating / video;
| /testimonials and /{entity}/testimonials with ?type= and the four layouts, dragged order,
| "What People Say" with a type filter, the Program tab.
*/

use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Program\Models\Program;
use Modules\Testimonials\Filament\Resources\Testimonials\Pages\CreateTestimonial;
use Modules\Testimonials\Filament\Resources\Testimonials\Pages\EditTestimonial;
use Modules\Testimonials\Models\Testimonial;
use Modules\Testimonials\Sections\TestimonialsSection;
use Modules\Testimonials\Settings\TestimonialsSettings;
use Modules\Testimonials\Support\Authors;
use Platform\Assignments\ManualPerson;
use Platform\Assignments\RoleCatalog;
use Platform\Filament\Components\PersonPicker;
use Platform\Homepage\SectionContext;
use Platform\Modules\ModulePermissions;
use Platform\Relations\RelationEngine;
use Platform\Support\Countries;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Testimonials');
    app(ModulePermissions::class)->syncAttachable('testimonials', [Testimonial::class]);
    app(RoleCatalog::class)->sync();
    $this->engine = app(RelationEngine::class);

    $this->person = fn (string $name, ?string $designation = null): ManualPerson => ManualPerson::query()
        ->create(['name' => ['en' => $name], 'designation' => $designation ? ['en' => $designation] : null, 'is_active' => true]);
    $this->make = function (string $quote, string $type, string $name, array $attributes = [], ?array $title = null, array $places = [], bool $main = true): Testimonial {
        $t = Testimonial::factory()->create(['quote' => ['en' => $quote], 'testimonial_type' => $type, ...$attributes]);
        app(Authors::class)->set($t, ($this->person)($name, 'Software Engineer'), $title);
        $this->engine->sync($t, $places, mainSite: $main, user: null);

        return $t;
    };
});

it('shows the main site\'s testimonials in their dragged order, by type, with the person\'s title (FR-TEST-01/02/04)', function () {
    $a = ($this->make)('DIU changed my life.', 'alumni', 'Rahim Uddin', title: ['en' => 'Batch 45, Engineer at X']);
    $b = ($this->make)('Great teachers.', 'student', 'Karima Akter');
    $c = ($this->make)('My son loves it.', 'parent', 'Abdul Karim', title: ['en' => 'Parent of a CSE student']);
    ($this->make)('Hidden.', 'student', 'Nobody', ['is_active' => false]);
    ($this->make)('Not on the main site.', 'student', 'Elsewhere', main: false);
    $c->update(['sort_order' => 1]);
    $a->update(['sort_order' => 2]);
    $b->update(['sort_order' => 3]);

    $this->get('/testimonials')->assertOk()
        ->assertSeeInOrder(['My son loves it.', 'Parent of a CSE student', 'DIU changed my life.', 'Batch 45, Engineer at X', 'Great teachers.', 'Software Engineer'])
        ->assertDontSee('Hidden.')->assertDontSee('Not on the main site.')
        ->assertSee('Students')->assertSee('Parents');                                               // type filter, only types that exist
    $this->get('/testimonials?type=student')->assertOk()->assertSee('Great teachers.')->assertDontSee('DIU changed my life.');
    foreach (['grid', 'masonry', 'slider'] as $view) {
        $this->get('/testimonials?view='.$view)->assertOk()->assertSee('testimonials--'.$view, false);
    }
});

it('shows only testimonials with a video in the video layout, as an embed', function () {
    ($this->make)('Watch my story.', 'alumni', 'Video Person', ['video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);
    ($this->make)('Text only.', 'alumni', 'Text Person');

    $this->get('/testimonials?view=video')->assertOk()->assertSee('Watch my story.')->assertDontSee('Text only.')
        ->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', false);
});

it('keeps type and rating valid', function () {
    expect(fn () => Testimonial::factory()->create(['testimonial_type' => 'teacher']))->toThrow(ValidationException::class)
        ->and(fn () => Testimonial::factory()->create(['rating' => 6]))->toThrow(ValidationException::class);
});

it('saves the person and their title from the form, and moves it to another person (FR-TEST-01)', function () {
    $first = ($this->person)('Mr. Jewel', 'Head of HR');
    $second = ($this->person)('Ms. Tania', 'CEO');

    $this->actingAs(superAdmin());
    Livewire::test(CreateTestimonial::class)
        ->fillForm([
            'person' => PersonPicker::keyOf($first), 'author_title' => ['en' => 'MBA alumnus'],
            'quote' => ['en' => 'The MBA helped my career.'], 'testimonial_type' => 'alumni', 'rating' => 5,
            'show_in' => [], 'main_site' => true,
        ])
        ->call('create')->assertHasNoFormErrors();

    $testimonial = Testimonial::query()->sole();
    expect($testimonial->person()->name())->toBe('Mr. Jewel')
        ->and($testimonial->author()->getTranslation('role_label', 'en'))->toBe('MBA alumnus');
    $this->get('/testimonials')->assertSee('Mr. Jewel')->assertSee('MBA alumnus')->assertSee('★★★★★');

    Livewire::test(EditTestimonial::class, ['record' => $testimonial->id])
        ->assertSet('data.person', PersonPicker::keyOf($first))->assertSet('data.author_title.en', 'MBA alumnus')
        ->fillForm(['person' => PersonPicker::keyOf($second), 'author_title' => ['en' => '']])
        ->call('save')->assertHasNoFormErrors();

    expect($testimonial->fresh()->person()->name())->toBe('Ms. Tania')
        ->and($testimonial->assignments()->count())->toBe(1);
    $this->get('/testimonials')->assertSee('Ms. Tania')->assertSee('CEO')->assertDontSee('MBA alumnus');   // no own title: the designation
});

it('shows "What People Say" with only the highlighted ones of the chosen types; more only when the builder asks (FR-TEST-03, owner 2026-10-24)', function () {
    $student = ($this->make)('Student one.', 'student', 'S One');
    ($this->make)('Alumni one.', 'alumni', 'A One');
    $star = ($this->make)('Student star.', 'student', 'S Star');
    $star->update(['sort_order' => 99]);
    $this->engine->sync($star, [], mainSite: true, highlights: ['site:0' => ['order' => 1]], user: null);

    expect(TestimonialsSection::defaultSourceMode())->toBe('highlighted');
    $items = (new TestimonialsSection)->items(new SectionContext('site:0', ['types' => ['student'], 'count' => 5], 'en'));
    expect($items->pluck('quote')->all())->toBe(['Student star.']);                                     // only the chosen one

    $items = (new TestimonialsSection)->items(new SectionContext('site:0', ['source_mode' => 'highlighted_first', 'types' => ['student'], 'count' => 5], 'en'));
    expect($items->pluck('quote')->all())->toBe(['Student star.', 'Student one.']);

    $latest = (new TestimonialsSection)->items(new SectionContext('site:0', ['source_mode' => 'latest', 'count' => 2], 'en'));
    expect($latest->pluck('quote')->all())->toBe(['Student one.', 'Alumni one.']);                     // "latest" = the dragged order
    expect($latest->first()->is($student))->toBeTrue();
});

it('shows a program\'s testimonials on its page and microsite', function () {
    $program = Program::factory()->create();
    ($this->make)('The program is great.', 'student', 'P Student', places: [$program->getMorphClass().':'.$program->id], main: false);

    $this->get($program->url())->assertOk()->assertSee('The program is great.');
    $this->get('/testimonials')->assertOk()->assertDontSee('The program is great.');
});

it('shows DIU\'s testimonials: two columns of cards that open to the whole testimonial, texts from Settings › Testimonials (Phase 4.5e)', function () {
    $long = str_repeat('DIU gave me the tools I needed. ', 12);
    $a = ($this->make)($long, 'alumni', 'Rahim Uddin', title: ['en' => 'Batch 45, Engineer at X']);

    $this->get('/testimonials')->assertOk()
        ->assertSeeInOrder(['Testimonials', 'Hear from students, faculty, and visitors', 'DIU gave me the tools', '...', 'Rahim Uddin', 'Batch 45, Engineer at X', 'Alumni', 'Read more'])
        ->assertSee('id="testimonial-'.$a->id.'"', false)                       // /testimonials#testimonial-{id} opens it
        ->assertSee(trim($long));                                                 // the whole testimonial, in its window

    // The module's own page (any theme without its version)
    expect(moduleView('Testimonials', 'index', ['items' => collect([$a]), 'type' => null, 'types' => ['alumni'], 'layout' => 'grid', 'base' => '']))->toContain('Rahim Uddin');

    settings()->saveFor(new TestimonialsSettings, ['title' => ['en' => 'Voices of DIU'], 'text' => ['en' => 'In their words.']]);
    $this->get('/testimonials')->assertSee('Voices of DIU')->assertSee('In their words.');
});

it('records where a testimonial\'s writer is from and marks an international voice on the website', function () {
    $local = ($this->make)('Great campus.', 'student', 'Karim', attributes: ['country' => null]);
    $abroad = ($this->make)('A welcoming place.', 'partner', 'Ms. Tanaka', attributes: ['country' => 'JP']);

    expect(Countries::options('en'))->toHaveKey('JP')->and(Countries::name('JP', 'en'))->toBe('Japan')
        ->and(Countries::isAbroad('BD'))->toBeFalse()->and(Countries::isAbroad('JP'))->toBeTrue();
    $this->get('/testimonials')->assertOk()->assertSeeInOrder(['Ms. Tanaka', 'Japan'])->assertDontSee('Bangladesh');

    $this->actingAs(superAdmin());
    Livewire::test(EditTestimonial::class, ['record' => $local->getRouteKey()])->fillForm(['country' => 'IN'])->call('save')->assertHasNoFormErrors();
    expect($local->fresh()->country)->toBe('IN');
});
