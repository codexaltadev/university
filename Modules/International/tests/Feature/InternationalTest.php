<?php

/*
| Phase 3.28 — M-35 International (DB-06 v1.3 §6, SRS Services FR-INTL-01..09): partner
| institutions by country, agreement and search in three layouts, the expired hidden or marked and
| told before, the Excel / CSV import, conferences, visitors, the counters and "Global Partners".
*/

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\International\Filament\Resources\Institutions\Pages\CreateInstitution;
use Modules\International\Metrics\CountriesCount;
use Modules\International\Metrics\PartnersCount;
use Modules\International\Models\Conference;
use Modules\International\Models\Country;
use Modules\International\Models\Institution;
use Modules\International\Models\Visitor;
use Modules\International\Sections\GlobalPartnersSection;
use Modules\International\Support\InstitutionImporter;
use Platform\Homepage\SectionContext;
use Platform\Modules\ModulePermissions;
use Platform\Relations\RelationEngine;
use Platform\Statistics\StatContext;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('International');
    app(ModulePermissions::class)->syncAttachable('international', [Institution::class, Conference::class, Visitor::class]);
    $this->engine = app(RelationEngine::class);
    $this->my = Country::factory()->create(['name' => ['en' => 'Malaysia'], 'iso2' => 'MY']);
    $this->jp = Country::factory()->create(['name' => ['en' => 'Japan'], 'iso2' => 'jp']);
    $this->make = function (string $class, array $attributes): mixed {
        $m = $class::factory()->create($attributes);
        $this->engine->sync($m, [], mainSite: true, user: null);

        return $m;
    };
});

it('lists partner institutions by country, agreement and search, in logos, table or countries (FR-INTL-02/03)', function () {
    ($this->make)(Institution::class, ['name' => ['en' => 'Universiti Malaya'], 'country_id' => $this->my->id, 'agreement_type' => 'mou', 'website_url' => 'https://um.edu.my']);
    ($this->make)(Institution::class, ['name' => ['en' => 'Taylor\'s University'], 'country_id' => $this->my->id, 'agreement_type' => 'exchange']);
    ($this->make)(Institution::class, ['name' => ['en' => 'Kyoto Tech'], 'country_id' => $this->jp->id, 'agreement_type' => 'research']);
    ($this->make)(Institution::class, ['name' => ['en' => 'Old Partner'], 'country_id' => $this->jp->id, 'expires_on' => now()->subDay()->toDateString()]);
    $soon = ($this->make)(Institution::class, ['name' => ['en' => 'Soon Ending'], 'country_id' => $this->jp->id, 'expires_on' => now()->addDays(60)->toDateString()]);

    expect($this->jp->iso2)->toBe('JP');
    $this->get('/international/partners')->assertOk()->assertSee('Universiti Malaya')->assertSee('Kyoto Tech')->assertDontSee('Old Partner')->assertSee('href="https://um.edu.my"', false);
    $this->get('/international/partners?country=MY')->assertOk()->assertSee('Taylor')->assertDontSee('Kyoto Tech');
    $this->get('/international/partners?type=research')->assertOk()->assertSee('Kyoto Tech')->assertDontSee('Universiti Malaya');
    $this->get('/international/partners?q=kyoto&view=table')->assertOk()->assertSee('Kyoto Tech')->assertDontSee('Universiti Malaya')->assertSee('<table', false);
    $this->get('/international/partners?view=countries')->assertOk()->assertSeeInOrder(['Japan', '2', 'Malaysia', '2']);

    settings()->save('international', ['expired' => 'label']);
    $this->get('/international/partners?country=JP')->assertOk()->assertSeeInOrder(['Old Partner', __('international::international.expired')]);

    $admin = superAdmin();
    Artisan::call('international:expiring');
    expect($admin->notifications()->count())->toBe(1)->and($soon->exists)->toBeTrue();
    expect((new PartnersCount)->count(StatContext::site(), []))->toBe(5)->and((new CountriesCount)->count(StatContext::site(), []))->toBe(2);
    expect(fn () => Institution::factory()->create(['agreement_type' => 'friendship']))->toThrow(ValidationException::class);
    expect(fn () => Country::factory()->create(['iso2' => 'MY']))->toThrow(ValidationException::class);
});

it('imports a partner list, checked first and without doubles (FR-INTL-07)', function () {
    ($this->make)(Institution::class, ['name' => ['en' => 'Universiti Malaya'], 'country_id' => $this->my->id]);
    $file = tempnam(sys_get_temp_dir(), 'intl').'.csv';
    file_put_contents($file, "Name,Country,Website,Agreement,Signed,Expires\nUniversiti Malaya,MY,um.edu.my,MoU,2024-01-10,2029-01-09\nOsaka University,Japan,,dual degree,,\nNowhere College,Atlantis,,,,\n,,,,,\n");

    $checked = app(InstitutionImporter::class)->check($file);
    expect($checked['total'])->toBe(3)->and($checked['rows'])->toHaveCount(2)->and($checked['errors'][0])->toContain('Line 4')->toContain('Atlantis');
    $this->actingAs(superAdmin());
    app(InstitutionImporter::class)->save($checked['rows']);
    @unlink($file);

    expect(Institution::query()->count())->toBe(2)
        ->and(Institution::query()->where('name->en', 'Universiti Malaya')->first()->website_url)->toBe('https://um.edu.my')
        ->and(Institution::query()->where('name->en', 'Osaka University')->value('agreement_type'))->toBe('dual_degree');
    $this->get('/international/partners')->assertSee('Osaka University');

    Livewire::test(CreateInstitution::class)->fillForm(['name' => ['en' => 'Seoul National'], 'country_id' => $this->jp->id, 'agreement_type' => 'mou', 'show_in' => [], 'main_site' => true])->call('create')->assertHasNoFormErrors();
    expect(Institution::query()->where('name->en', 'Seoul National')->exists())->toBeTrue();
});

it('shows conferences, visitors and "Global Partners" with the featured first (FR-INTL-04/05/06/09)', function () {
    ($this->make)(Conference::class, ['title' => ['en' => 'ICCIT 2026'], 'starts_on' => now()->addMonth()->toDateString(), 'venue' => ['en' => 'DSC'], 'body' => ['en' => '<p>Call for papers.</p>']]);
    ($this->make)(Conference::class, ['title' => ['en' => 'ICCIT 2020'], 'starts_on' => '2020-12-01']);
    ($this->make)(Visitor::class, ['name' => ['en' => 'Prof. Tanaka'], 'organization' => ['en' => 'Kyoto University'], 'country_id' => $this->jp->id, 'visited_on' => '2025-03-01']);
    $a = ($this->make)(Institution::class, ['name' => ['en' => 'Alpha Uni'], 'country_id' => $this->my->id, 'sort_order' => 1]);
    $b = ($this->make)(Institution::class, ['name' => ['en' => 'Beta Uni'], 'country_id' => $this->jp->id, 'sort_order' => 2]);
    $this->engine->sync($b, [], mainSite: true, highlights: ['site:0' => ['order' => 1]], user: null);

    $this->get('/international/conferences')->assertOk()->assertSeeInOrder([__('international::international.upcoming'), 'ICCIT 2026', __('international::international.past'), 'ICCIT 2020']);
    $this->get(Conference::query()->where('title->en', 'ICCIT 2026')->first()->url())->assertOk()->assertSee('Call for papers.');
    $this->get('/international/visitors')->assertOk()->assertSee('Prof. Tanaka')->assertSee('Kyoto University')->assertSee('Japan');
    $this->get('/international')->assertOk()->assertSee(__('international::international.featured'))->assertSee('Beta Uni');
    expect((new GlobalPartnersSection)->items(new SectionContext('site:0', ['count' => 5], 'en'))->pluck('name')->all())->toBe(['Beta Uni', 'Alpha Uni']);
});
