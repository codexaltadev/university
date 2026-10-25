<?php

/*
| Phase 3.19 — M-24 Achievements (DB-05 §9 v1.8, SRS Content FR-ACH-01..04): the achievers are
| people (Person Picker, a note each), a team name optional; level, competition, issuer,
| position; /achievements and /{entity}/achievements by year, level, category in cards /
| timeline / grid; the detail page; "Recent Achievements" with a level; the counter by level.
*/

use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Achievements\Filament\Resources\Achievements\Pages\CreateAchievement;
use Modules\Achievements\Metrics\AchievementsCount;
use Modules\Achievements\Models\Achievement;
use Modules\Achievements\Sections\RecentAchievementsSection;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Platform\Assignments\AssignmentEngine;
use Platform\Assignments\ManualPerson;
use Platform\Assignments\RoleCatalog;
use Platform\Homepage\SectionContext;
use Platform\Media\MediaItem;
use Platform\Media\MediaLibrary;
use Platform\Modules\ModulePermissions;
use Platform\Relations\RelationEngine;
use Platform\Statistics\StatContext;
use Platform\Taxonomy\Vocabularies;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Achievements');
    app(ModulePermissions::class)->syncAttachable('achievements', [Achievement::class]);
    app(RoleCatalog::class)->sync();
    $this->engine = app(RelationEngine::class);
    $this->cse = Department::factory()->create(['faculty_id' => Faculty::factory()->create()->id, 'short_name' => 'CSE']);
    $this->cseKey = $this->cse->getMorphClass().':'.$this->cse->id;

    $this->make = function (string $title, string $level, string $on, array $places = [], bool $main = true, array $attributes = []): Achievement {
        $a = Achievement::factory()->create(['title' => ['en' => $title], 'level' => $level, 'achieved_on' => $on, ...$attributes]);
        $this->engine->sync($a, $places, mainSite: $main, user: null);

        return $a;
    };
});

it('lists the main site\'s achievements by the date achieved, filtered by year, level and category (FR-ACH-02)', function () {
    Vocabularies::ensureTerms(Achievement::CATEGORY, [['name' => ['en' => 'Programming'], 'slug' => 'programming']]);
    $term = (int) array_search('Programming', app(Vocabularies::class)->options(Achievement::CATEGORY), true);
    $icpc = ($this->make)('ICPC Asia West Champion', 'international', '2025-12-10', attributes: ['position_label' => ['en' => 'Champion']]);
    $icpc->syncTerms(Achievement::CATEGORY, [$term]);
    ($this->make)('National Hackathon Runner-up', 'national', '2026-03-01');
    ($this->make)('Old Debate Win', 'regional', '2023-05-05');
    ($this->make)('Hidden one', 'national', '2026-01-01', attributes: ['is_active' => false]);
    ($this->make)('CSE only win', 'institutional', '2026-02-01', [$this->cseKey], main: false);

    $this->get('/achievements')->assertOk()
        ->assertSeeInOrder(['National Hackathon Runner-up', 'ICPC Asia West Champion', 'Old Debate Win'])
        ->assertSeeInOrder(['All', 'International', 'National', 'Regional', 'Institutional'])                     // the level switch (Phase 4.5f)
        ->assertSee('International · Champion')
        ->assertDontSee('Hidden one')->assertDontSee('CSE only win');
    $this->get('/achievements?level=international')->assertOk()->assertSee('ICPC Asia West Champion')->assertDontSee('National Hackathon');
    $this->get('/achievements?year=2023')->assertOk()->assertSee('Old Debate Win')->assertDontSee('ICPC');
    $this->get('/achievements?category=programming')->assertOk()->assertSee('ICPC')->assertDontSee('Old Debate Win');
    foreach (['timeline', 'grid'] as $view) {
        $this->get('/achievements?view='.$view)->assertOk()->assertSee('achievement-card--'.$view, false);
    }

    $this->get(rtrim($this->cse->url(), '/').'/achievements')->assertOk()->assertSee('CSE only win')->assertDontSee('ICPC');
});

it('shows the achievers with their note and picture, the details and the related news (FR-ACH-01/04)', function () {
    $a = ($this->make)('ICPC Asia West Champion', 'international', '2025-12-10', attributes: [
        'competition' => ['en' => 'ICPC Asia West Continent Final'], 'issuer' => ['en' => 'ICPC Foundation'], 'team_name' => ['en' => 'DIU Mavericks'],
    ]);
    $lead = ManualPerson::query()->create(['name' => ['en' => 'Rahim Uddin'], 'designation' => ['en' => 'Student, CSE'], 'is_active' => true]);
    $coach = ManualPerson::query()->create(['name' => ['en' => 'Dr. Karim'], 'designation' => ['en' => 'Associate Professor'], 'is_active' => true]);
    app(AssignmentEngine::class)->assign($lead, $a, Achievement::ROLE, ['note' => ['en' => 'Team lead'], 'is_public' => true]);
    app(AssignmentEngine::class)->assign($coach, $a, Achievement::ROLE, ['is_public' => true]);
    $picture = app(MediaLibrary::class)->store(UploadedFile::fake()->image('team.jpg', 40, 30));
    $a->syncMedia('image', [$picture->id]);

    $this->get($a->url())->assertOk()
        ->assertSee('ICPC Asia West Continent Final')->assertSee('ICPC Foundation')->assertSee('DIU Mavericks')
        ->assertSeeInOrder(['Achieved by', 'Rahim Uddin', 'Team lead', 'Dr. Karim', 'Associate Professor'])
        ->assertSee($picture->url('large') ?? $picture->url(), false)
        ->assertSee('"@type":"Article"', false);
    expect(MediaItem::query()->count())->toBe(1);                                                       // used, not copied
});

it('keeps level and date valid', function () {
    expect(fn () => Achievement::factory()->create(['level' => 'galactic']))->toThrow(ValidationException::class)
        ->and(fn () => Achievement::factory()->create(['achieved_on' => null]))->toThrow(ValidationException::class);
});

it('fills "Recent Achievements" newest first, one level only, highlighted first (FR-ACH-03)', function () {
    $older = ($this->make)('Older international', 'international', '2024-01-01');
    $newer = ($this->make)('Newer international', 'international', '2026-01-01');
    ($this->make)('A national one', 'national', '2026-05-01');
    $star = ($this->make)('Star international', 'international', '2023-01-01');
    $this->engine->sync($star, [], mainSite: true, highlights: ['site:0' => ['order' => 1]], user: null);

    $items = (new RecentAchievementsSection)->items(new SectionContext('site:0', ['level' => 'international', 'count' => 3], 'en'));
    expect($items->pluck('title')->all())->toBe(['Star international', 'Newer international', 'Older international']);

    $latest = (new RecentAchievementsSection)->items(new SectionContext('site:0', ['source_mode' => 'latest', 'count' => 2], 'en'));
    expect($latest->pluck('title')->all())->toBe(['A national one', 'Newer international']);
});

it('counts achievements by level for a statistics counter (FR-ACH-03)', function () {
    ($this->make)('One', 'international', '2025-01-01');
    ($this->make)('Two', 'international', '2025-02-01', [$this->cseKey]);
    ($this->make)('Three', 'national', '2025-03-01', [$this->cseKey], main: false);

    $metric = new AchievementsCount;
    expect($metric->count(StatContext::site(), ['level' => 'international']))->toBe(2)
        ->and($metric->count(StatContext::site(), []))->toBe(3)
        ->and($metric->count(StatContext::of($this->cseKey), []))->toBe(2);
});

it('is made in the admin', function () {
    $this->actingAs(superAdmin());
    Livewire::test(CreateAchievement::class)
        ->fillForm(['title' => ['en' => 'Robotics Olympiad Gold'], 'achieved_on' => '2026-09-01', 'level' => 'national', 'show_in' => [], 'main_site' => true])
        ->call('create')->assertHasNoFormErrors();

    $this->get(Achievement::query()->sole()->url())->assertOk()->assertSee('Robotics Olympiad Gold');
});
