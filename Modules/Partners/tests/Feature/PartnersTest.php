<?php

/*
| Phase 3.26 — M-37 Partners (DB-06 v1.3 §8, SRS Services FR-PRT-01..05): partners by type, student
| discounts by category with expired ones hidden, a department's partners, and the logo carousel.
*/

use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\Partners\Filament\Resources\Partners\Pages\CreatePartner;
use Modules\Partners\Models\Partner;
use Modules\Partners\Sections\PartnersSection;
use Platform\Homepage\SectionContext;
use Platform\Modules\ModulePermissions;
use Platform\Relations\RelationEngine;
use Platform\Taxonomy\Vocabularies;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Partners');
    app(ModulePermissions::class)->syncAttachable('partners', [Partner::class]);
    $this->engine = app(RelationEngine::class);
    $this->make = function (string $name, array $attributes = [], array $places = [], bool $main = true): Partner {
        $p = Partner::factory()->create(['name' => ['en' => $name], ...$attributes]);
        $this->engine->sync($p, $places, mainSite: $main, user: null);

        return $p;
    };
});

it('shows partners by type and the current student discounts by category (FR-PRT-02/04)', function () {
    ($this->make)('Acme Software', ['website_url' => 'https://acme.example.com']);
    ($this->make)('IEEE', ['partner_type' => 'membership']);
    Vocabularies::ensureTerms(Partner::CATEGORY, [['name' => ['en' => 'Food'], 'slug' => 'food'], ['name' => ['en' => 'Travel'], 'slug' => 'travel']]);
    $food = (int) array_search('Food', app(Vocabularies::class)->options(Partner::CATEGORY), true);
    ($this->make)('Pizza Place', ['partner_type' => 'discount', 'discount_value' => ['en' => '20% off'], 'valid_until' => now()->addMonth()->toDateString()])->syncTerms(Partner::CATEGORY, [$food]);
    ($this->make)('Old Cafe', ['partner_type' => 'discount', 'valid_until' => now()->subDay()->toDateString()]);
    ($this->make)('Future Gym', ['partner_type' => 'discount', 'valid_from' => now()->addWeek()->toDateString()]);

    $this->get('/partners')->assertOk()->assertSee('Acme Software')->assertSee('IEEE')->assertSee('href="https://acme.example.com"', false)
        ->assertDontSee('Pizza Place')->assertSee(__('partners::partners.see_discounts'));
    $this->get('/partners?type=membership')->assertOk()->assertSee('IEEE')->assertDontSee('Acme Software');
    $this->get('/student-discounts')->assertOk()->assertSeeInOrder(['Food', 'Pizza Place', '20% off'])->assertDontSee('Old Cafe')->assertDontSee('Future Gym');
});

it('keeps a department\'s partners there, fills the carousel highlights first (FR-PRT-03/05) and checks the input', function () {
    $cse = Department::factory()->create(['faculty_id' => Faculty::factory()->create()->id, 'short_name' => 'CSE']);
    ($this->make)('CSE Lab Partner', [], ['department.department:'.$cse->id], main: false);
    $a = ($this->make)('Alpha Corp', ['sort_order' => 1]);
    $b = ($this->make)('Beta Bank', ['partner_type' => 'sponsor', 'sort_order' => 2]);
    ($this->make)('Gamma Discount', ['partner_type' => 'discount']);
    $this->engine->sync($b, [], mainSite: true, highlights: ['site:0' => ['order' => 1]], user: null);

    $this->get('/partners')->assertDontSee('CSE Lab Partner');
    $this->get(rtrim($cse->url(), '/').'/partners')->assertOk()->assertSee('CSE Lab Partner')->assertDontSee('Alpha Corp');
    expect((new PartnersSection)->items(new SectionContext('site:0', ['count' => 5], 'en'))->pluck('name')->all())->toBe(['Beta Bank', 'Alpha Corp']);
    expect((new PartnersSection)->items(new SectionContext('site:0', ['count' => 5, 'types' => ['sponsor']], 'en'))->pluck('name')->all())->toBe(['Beta Bank']);

    expect(fn () => Partner::factory()->create(['partner_type' => 'friend']))->toThrow(ValidationException::class);
    expect(fn () => Partner::factory()->create(['valid_from' => '2026-05-01', 'valid_until' => '2026-04-01']))->toThrow(ValidationException::class);

    $this->actingAs(superAdmin());
    Livewire::test(CreatePartner::class)->fillForm(['partner_type' => 'discount', 'name' => ['en' => 'Book Shop'], 'discount_value' => ['en' => '10%'], 'show_in' => [], 'main_site' => true])->call('create')->assertHasNoFormErrors();
    expect(Partner::query()->where('name->en', 'Book Shop')->value('partner_type'))->toBe('discount');
});
