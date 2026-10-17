<?php

/*
| Phase 3.27 — M-34 Accreditation & Ranking (DB-06 v1.3 §5, SRS Services FR-ACR-01..06):
| accreditations by category with the expired hidden or marked, the notice before the end, a
| program's tab, rankings with the latest first and each agency's trend, and the homepage strip.
*/

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Accreditation\Filament\Resources\Rankings\Pages\CreateRanking;
use Modules\Accreditation\Models\Accreditation;
use Modules\Accreditation\Models\Ranking;
use Modules\Accreditation\Sections\RecognitionSection;
use Modules\Program\Models\Program;
use Platform\Homepage\SectionContext;
use Platform\Modules\ModulePermissions;
use Platform\Relations\RelationEngine;
use Platform\Taxonomy\Vocabularies;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Accreditation');
    app(ModulePermissions::class)->syncAttachable('accreditation', [Accreditation::class, Ranking::class]);
    $this->engine = app(RelationEngine::class);
    $this->accredit = function (string $title, array $attributes = [], array $places = [], bool $main = true): Accreditation {
        $a = Accreditation::factory()->create(['title' => ['en' => $title], ...$attributes]);
        $this->engine->sync($a, $places, mainSite: $main, user: null);

        return $a;
    };
    $this->rank = function (string $agency, int $year, string $rank, array $attributes = []): Ranking {
        $r = Ranking::factory()->create(['agency_name' => ['en' => $agency], 'agency_key' => '', 'year' => $year, 'rank_label' => $rank, ...$attributes]);
        $this->engine->sync($r, [], mainSite: true, user: null);

        return $r;
    };
});

it('shows accreditations by category, hides or marks the expired, and tells the admins before the end (FR-ACR-02/05)', function () {
    Vocabularies::ensureTerms(Accreditation::CATEGORY, [['name' => ['en' => 'National'], 'slug' => 'national'], ['name' => ['en' => 'International'], 'slug' => 'international']]);
    $options = app(Vocabularies::class)->options(Accreditation::CATEGORY);
    ($this->accredit)('IEB Accreditation', ['body' => ['en' => '<p>Engineering programs.</p>']])->syncTerms(Accreditation::CATEGORY, [(int) array_search('National', $options, true)]);
    ($this->accredit)('ACBSP', [])->syncTerms(Accreditation::CATEGORY, [(int) array_search('International', $options, true)]);
    ($this->accredit)('Old Seal', ['expires_on' => now()->subDay()->toDateString()]);
    $soon = ($this->accredit)('BAETE', ['expires_on' => now()->addDays(60)->toDateString()]);

    $this->get('/accreditations')->assertOk()->assertSeeInOrder(['National', 'IEB Accreditation', 'International', 'ACBSP'])->assertDontSee('Old Seal');
    settings()->save('accreditation', ['expired' => 'label']);
    $this->get('/accreditations')->assertOk()->assertSeeInOrder(['Old Seal', __('accreditation::accreditation.expired')]);
    $this->get(Accreditation::query()->where('title->en', 'IEB Accreditation')->first()->url())->assertOk()->assertSee('Engineering programs.');

    $admin = superAdmin();
    Artisan::call('accreditation:expiring');
    expect($admin->notifications()->count())->toBe(1)->and($soon->expires_on->isFuture())->toBeTrue();

    $program = Program::factory()->create();
    ($this->accredit)('Program Seal', [], [$program->getMorphClass().':'.$program->id], main: false);
    $this->get($program->url())->assertOk()->assertSee('Program Seal');
    $this->get('/accreditations')->assertDontSee('Program Seal');
});

it('shows each ranking\'s latest year first, the trend by agency, and the homepage strip (FR-ACR-03/04/06)', function () {
    ($this->rank)('QS World University Rankings', 2024, '1001–1200');
    ($this->rank)('QS World University Rankings', 2025, '801–1000');
    ($this->rank)('THE Impact Rankings', 2025, '#45');
    ($this->rank)('Webometrics', 2023, '2101');
    ($this->accredit)('IEB Accreditation');

    expect(Ranking::query()->where('rank_label', '801–1000')->value('rank_value'))->toBe(801)
        ->and(Ranking::query()->where('rank_label', '#45')->value('agency_key'))->toBe('the-impact-rankings');
    $this->get('/rankings')->assertOk()->assertSeeInOrder(['801–1000', '#45', '2101', __('accreditation::accreditation.trend'), 'QS World University Rankings', '2025', '801–1000', '2024', '1001–1200']);
    expect(fn () => ($this->rank)('QS World University Rankings', 2025, '700'))->toThrow(ValidationException::class);

    $items = (new RecognitionSection)->items(new SectionContext('site:0', ['count' => 10], 'en'));
    expect($items->map(fn ($i) => $i instanceof Ranking ? $i->rank_label : $i->title)->all())->toBe(['801–1000', '#45', '2101', 'IEB Accreditation']);

    $this->actingAs(superAdmin());
    Livewire::test(CreateRanking::class)->fillForm(['agency_name' => ['en' => 'QS Asia'], 'agency_key' => 'qs-asia', 'scope' => 'region', 'subject' => ['en' => 'Asia'], 'year' => 2025, 'rank_label' => '351', 'show_in' => [], 'main_site' => true])
        ->call('create')->assertHasNoFormErrors();
    expect(Ranking::query()->where('agency_key', 'qs-asia')->value('subject_key'))->toBe('asia');
});
