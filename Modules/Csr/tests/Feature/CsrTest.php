<?php

/*
| Phase 3.25 — M-36 CSR (DB-06 v1.3 §7, SRS Services FR-CSR-01..04): category cards with their
| Media Library picture, a category's activities (sub categories included), the activity page, and
| the homepage section with highlights first.
*/

use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Csr\Filament\Resources\Activities\Pages\CreateActivity;
use Modules\Csr\Models\Activity;
use Modules\Csr\Sections\CsrSection;
use Modules\Csr\Support\Categories;
use Modules\Csr\Support\CsrWebsite;
use Platform\Context\SiteContext;
use Platform\Homepage\SectionContext;
use Platform\Media\MediaLibrary;
use Platform\Modules\ModulePermissions;
use Platform\Relations\RelationEngine;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Csr');
    app(ModulePermissions::class)->syncAttachable('csr', [Activity::class]);
    $this->engine = app(RelationEngine::class);
    $this->make = function (string $title, array $attributes = [], bool $main = true): Activity {
        $a = Activity::factory()->create(['title' => ['en' => $title], ...$attributes]);
        $this->engine->sync($a, [], mainSite: $main, user: null);

        return $a;
    };
});

it('shows category cards, a category with its sub categories, and the activity (FR-CSR-02)', function () {
    Vocabularies::ensureTerms(Activity::CATEGORY, [['name' => ['en' => 'Environment'], 'slug' => 'environment'], ['name' => ['en' => 'Health'], 'slug' => 'health']]);
    $env = Term::query()->where('name->en', 'Environment')->firstOrFail();
    $env->update(['description' => ['en' => 'Green campus work.']]);
    $trees = Term::query()->create(['vocabulary_id' => $env->vocabulary_id, 'parent_id' => $env->id, 'name' => ['en' => 'Tree Plantation']]);
    $picture = app(MediaLibrary::class)->store(UploadedFile::fake()->image('trees.jpg', 40, 30));
    app(Categories::class)->setImage($env, $picture->id);

    $planting = ($this->make)('Planting 1000 trees', ['occurred_on' => '2025-06-05', 'body' => ['en' => '<p>Students planted trees.</p>'], 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']);
    $planting->syncTerms(Activity::CATEGORY, [$trees->id]);
    ($this->make)('Blood donation')->syncTerms(Activity::CATEGORY, [Term::query()->where('name->en', 'Health')->value('id')]);
    ($this->make)('Closed Pool', ['is_active' => false]);

    $this->get('/csr')->assertOk()->assertSeeInOrder(['Where We Create Impact', 'Environment', 'Green campus work.', 'Stories of Change', 'Planting 1000 trees'])   // DIU's page: focus areas with a picture or line
        ->assertSee($picture->url('medium') ?? $picture->url(), false)->assertSee('50,000+')->assertSee('Individuals Supported');
    expect(moduleView('Csr', 'index', app(CsrWebsite::class)->respond('csr', SiteContext::site(), 'en')->getOriginalContent()->getData()))
        ->toContain('Green campus work.')->not->toContain('Planting 1000 trees');                   // the module's own: the categories first
    $this->get('/csr?category=health')->assertOk()->assertSee('Blood donation')->assertDontSee('Planting 1000 trees');
    $this->get('/csr?q=planting')->assertOk()->assertSee('Planting 1000 trees')->assertDontSee('Blood donation');
    $this->get('/csr/category/'.$env->slug())->assertOk()->assertSee('Tree Plantation')->assertSee('Planting 1000 trees')->assertDontSee('Blood donation');
    $this->get($planting->url())->assertOk()->assertSee('Students planted trees.')->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false);
    $this->get('/csr/category/nothing')->assertNotFound();
});

it('lists activities newest first when there are no categories, and fills the homepage with highlights first (FR-CSR-03)', function () {
    ($this->make)('Old drive', ['occurred_on' => '2020-01-01']);
    $new = ($this->make)('Flood relief', ['occurred_on' => '2025-08-01']);
    $hl = ($this->make)('Winter clothes', ['occurred_on' => '2022-12-01']);
    $this->engine->sync($hl, [], mainSite: true, highlights: ['site:0' => ['order' => 1]], user: null);

    $this->get('/csr')->assertOk()->assertSeeInOrder(['Flood relief', 'Winter clothes', 'Old drive']);
    expect((new CsrSection)->items(new SectionContext('site:0', ['count' => 2], 'en'))->pluck('title')->all())->toBe(['Winter clothes', 'Flood relief']);
    expect(fn () => Activity::factory()->create(['external_url' => 'javascript:alert(1)']))->toThrow(ValidationException::class);
    expect($new->videoEmbed())->toBeNull();

    $this->actingAs(superAdmin());
    Livewire::test(CreateActivity::class)->fillForm(['title' => ['en' => 'Eye camp'], 'show_in' => [], 'main_site' => true])->call('create')->assertHasNoFormErrors();
    expect(Activity::query()->where('title->en', 'Eye camp')->exists())->toBeTrue();
    $this->get('/admin/csr-categories')->assertOk();
});
