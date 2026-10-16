<?php

/*
| Phase 3.18 — M-23 Slider / Hero (DB-05 §8 v1.7, SRS Content FR-SLD-01..07): a place's hero is
| its slides — one is still, several a slider, a video plays behind the text; one slide shows in
| many places; banner and parent fallback; from / until; the main homepage's hero section.
*/

use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\Homepage\Support\Homepages;
use Modules\Slider\Filament\Resources\Slides\Pages\CreateSlide;
use Modules\Slider\Models\Slide;
use Modules\Slider\Sections\HeroSliderSection;
use Modules\Slider\Support\Hero;
use Platform\Homepage\SectionContext;
use Platform\Media\MediaItem;
use Platform\Media\MediaLibrary;
use Platform\Modules\ModulePermissions;
use Platform\Relations\RelationEngine;

beforeEach(function () {
    Carbon::setTestNow('2026-10-16 10:00:00');
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Slider');
    app(ModulePermissions::class)->syncAttachable('slider', [Slide::class]);
    $this->engine = app(RelationEngine::class);
    $this->hero = app(Hero::class);
    $this->picture = fn (string $name): MediaItem => app(MediaLibrary::class)->store(UploadedFile::fake()->image($name, 60, 34));

    $this->fsit = Faculty::factory()->create(['short_name' => 'FSIT']);
    $this->cse = Department::factory()->create(['faculty_id' => $this->fsit->id, 'short_name' => 'CSE']);
    $this->key = fn ($m): string => $m->getMorphClass().':'.$m->id;

    $this->slide = function (string $title, array $places = [], bool $main = false, array $attributes = [], bool $banner = false): Slide {
        $slide = Slide::factory()->create(['title' => ['en' => $title], 'placement' => $banner ? 'page_banner' : 'hero', ...$attributes]);
        $slide->syncMedia('image_desktop', [($this->picture)(str($title)->slug().'.jpg')->id]);
        $this->engine->sync($slide, $places, mainSite: $main, user: null);

        return $slide;
    };
    $this->render = fn (string $owner, array $settings = []): string => view('slider::sections.hero', [
        'items' => (new HeroSliderSection)->items(new SectionContext($owner, $settings, 'en')), 'section' => null, 'settings' => $settings,
    ])->render();
});

afterEach(fn () => Carbon::setTestNow());

it('shows one slide in many places, and a program\'s only in its own hero (FR-SLD-03, AC-SLD-01)', function () {
    $both = ($this->slide)('Admission Fall 2026', [($this->key)($this->cse)], main: true);
    $cseOnly = ($this->slide)('CSE Robotics Week', [($this->key)($this->cse)]);

    expect($this->hero->for('site:0')->pluck('id')->all())->toBe([$both->id])
        ->and($this->hero->for(($this->key)($this->cse))->pluck('id')->all())->toBe([$both->id, $cseOnly->id]);

    $bare = Slide::factory()->create(['title' => ['en' => 'No picture yet']]);                          // nothing to show: left out
    $this->engine->sync($bare, [], mainSite: true, user: null);
    expect($this->hero->for('site:0')->pluck('id')->all())->toBe([$both->id]);
});

it('is still with one slide and a slider with several (FR-SLD-05)', function () {
    ($this->slide)('Welcome to DIU', main: true, attributes: ['buttons' => [['label' => ['en' => 'Learn More'], 'url' => '/rankings']], 'bullets' => ['en' => ['#1 in Bangladesh', 'Global Top-20 in SDG 4']]]);

    $html = ($this->render)('site:0');
    expect($html)->toContain('Welcome to DIU')->toContain('Learn More')->toContain('href="/rankings"')->toContain('Global Top-20 in SDG 4')
        ->not->toContain('aria-label="Next slide"');

    ($this->slide)('Convocation', main: true);
    expect(($this->render)('site:0', ['arrows' => true, 'dots' => true]))->toContain('aria-label="Next slide"')->toContain('aria-label="Slide 2"');
});

it('plays a background video muted in a loop with the picture as poster, on phones only when asked', function () {
    $slide = ($this->slide)('Shaping futures', main: true);
    $mp4 = pack('N', 24).'ftypmp42'.pack('N', 0).'mp42isom'.pack('N', 8).'free';                   // a minimal real MP4 header
    $video = app(MediaLibrary::class)->store(UploadedFile::fake()->createWithContent('hero.mp4', $mp4));
    expect($video->kind)->toBe('video');
    $slide->syncMedia('video', [$video->id]);

    $html = ($this->render)('site:0');
    expect($html)->toContain('<video autoplay muted loop playsinline')->toContain('poster="')->toContain('data-desktop-only')->toContain('prefers-reduced-motion');

    $slide->update(['video_on_mobile' => true]);
    expect(substr_count($html, 'data-desktop-only'))->toBe(2)                                           // the style rule and the video
        ->and(substr_count(($this->render)('site:0'), 'data-desktop-only'))->toBe(1);                   // only the style rule

    $slide->syncMedia('video', [($this->picture)('not-a-video.jpg')->id]);                            // only a real video plays
    expect($slide->fresh()->videoItem())->toBeNull();
});

it('falls back to the place\'s banner, then a parent\'s hero (FR-SLD-06)', function () {
    $facultyHero = ($this->slide)('FSIT welcomes you', [($this->key)($this->fsit)]);
    expect($this->hero->for(($this->key)($this->cse))->pluck('id')->all())->toBe([$facultyHero->id]);   // CSE has none: its faculty's

    $banner = ($this->slide)('CSE cover', [($this->key)($this->cse)], banner: true);
    expect($this->hero->for(($this->key)($this->cse))->pluck('id')->all())->toBe([$banner->id]);

    $own = ($this->slide)('CSE hero', [($this->key)($this->cse)]);
    expect($this->hero->for(($this->key)($this->cse))->pluck('id')->all())->toBe([$own->id])
        ->and($this->hero->for('site:0'))->toBeEmpty();                                                // the main site never borrows
});

it('shows a campaign slide only from its start until its end (FR-SLD-04)', function () {
    ($this->slide)('Admission open', main: true, attributes: ['active_at' => '2026-10-20 00:00', 'expires_at' => '2026-11-30 23:59']);
    expect($this->hero->for('site:0'))->toBeEmpty();

    Carbon::setTestNow('2026-11-01 10:00:00');
    expect($this->hero->for('site:0'))->toHaveCount(1);
    Carbon::setTestNow('2026-12-01 10:00:00');
    expect($this->hero->for('site:0'))->toBeEmpty();

    expect(fn () => Slide::factory()->create(['active_at' => '2026-12-10', 'expires_at' => '2026-12-01']))->toThrow(ValidationException::class)
        ->and(fn () => Slide::factory()->create(['buttons' => array_fill(0, 3, ['label' => ['en' => 'x'], 'url' => '/x'])]))->toThrow(ValidationException::class)
        ->and(fn () => Slide::factory()->create(['bullets' => ['en' => ['a', 'b', 'c', 'd', 'e']]]))->toThrow(ValidationException::class);
});

it('is made in the admin with pictures from the Media Library and shown on the main homepage', function () {
    $picture = ($this->picture)('campus.jpg');
    $this->actingAs(superAdmin());
    Livewire::test(CreateSlide::class)
        ->fillForm([
            'placement' => 'hero', 'title' => ['en' => 'Ranked among the world\'s best'], 'image_desktop' => $picture->id,
            'buttons' => [['label' => ['en' => 'Learn More'], 'url' => '/rankings']], 'text_position' => 'center', 'overlay_opacity' => 40,
            'show_in' => [], 'main_site' => true,
        ])
        ->call('create')->assertHasNoFormErrors();

    expect(Slide::query()->sole()->desktopImage()->is($picture))->toBeTrue()
        ->and(MediaItem::query()->count())->toBe(1);                                                    // used, not copied

    $layout = app(Homepages::class)->create('site:0');
    $layout->sections()->create(['section_type' => 'slider.hero', 'sort_order' => 1, 'settings' => ['height' => 'screen']]);
    $this->get('/')->assertOk()->assertSee('Ranked among the world&#039;s best', false)->assertSee('100svh');
});
