<?php

/*
| Phase 3.4c — Scholarship (M-10, FR-FEE-10, DB-03 §11.3 v1.4): scholarships with one level
| of calls / opportunities, a deadline that closes them, /scholarships and
| /scholarships/{slug}, search and sitemap; the Waiver Office gets the permissions.
*/

use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Menu\Support\MenuLinks;
use Modules\Scholarship\Blocks\ScholarshipsBlock;
use Modules\Scholarship\Database\Seeders\ScholarshipDatabaseSeeder;
use Modules\Scholarship\Filament\Resources\Scholarships\Pages\CreateScholarship;
use Modules\Scholarship\Filament\Resources\Scholarships\Pages\ListScholarships;
use Modules\Scholarship\Filament\Resources\Scholarships\ScholarshipResource;
use Modules\Scholarship\Models\Scholarship;
use Platform\Modules\ModulePermissions;
use Platform\Seo\Sitemap;
use Platform\Taxonomy\Vocabularies;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Scholarship');

    $this->diu = Scholarship::factory()->create(['title' => ['en' => 'DIU Scholarship'], 'summary' => ['en' => 'Merit and need based.'], 'sort_order' => 1]);
    $this->erasmus = Scholarship::factory()->create(['title' => ['en' => 'Erasmus Scholarship'], 'sort_order' => 2]);
    $this->spring = Scholarship::factory()->partOf($this->erasmus)->create(['title' => ['en' => 'Spring 2027 exchange at Lodz'], 'deadline_at' => now()->addMonth(), 'apply_url' => 'https://apply.example.edu/lodz', 'sort_order' => 2]);
    $this->fall = Scholarship::factory()->partOf($this->erasmus)->create(['title' => ['en' => 'Fall 2024 exchange at Halic'], 'deadline_at' => now()->subYear(), 'apply_url' => 'https://apply.example.edu/halic', 'sort_order' => 1]);
});

it('adds a scholarship and a call under it from the admin (FR-FEE-10)', function () {
    $this->actingAs(superAdmin());

    Livewire::test(CreateScholarship::class)
        ->fillForm(['title' => ['en' => 'Korean Government Scholarship'], 'eligibility' => ['en' => '<p>CGPA 3.0</p>'], 'web_address' => 'gks'])
        ->call('create')->assertHasNoFormErrors();
    $gks = Scholarship::query()->get()->first(fn (Scholarship $s): bool => $s->title === 'Korean Government Scholarship');
    expect($gks->parent_id)->toBeNull()->and($gks->url())->toBe('/scholarships/gks');

    Livewire::withQueryParams(['parent' => $gks->id])->test(CreateScholarship::class)
        ->assertFormSet(['parent_id' => $gks->id])
        ->fillForm(['title' => ['en' => 'GKS 2027 call'], 'deadline_at' => now()->addWeeks(2)->toDateTimeString()])
        ->call('create')->assertHasNoFormErrors();
    expect($gks->parts()->count())->toBe(1);

    Livewire::test(ListScholarships::class)
        ->filterTable('parent_id', $this->erasmus->id)
        ->assertCanSeeTableRecords([$this->spring, $this->fall])->assertCanNotSeeTableRecords([$this->diu, $gks]);
});

it('keeps one level and does not remove a scholarship that has calls', function () {
    expect(fn () => $this->diu->update(['parent_id' => $this->spring->id]))->toThrow(ValidationException::class)       // not under a call
        ->and(fn () => $this->erasmus->update(['parent_id' => $this->diu->id]))->toThrow(ValidationException::class)   // has calls itself
        ->and(fn () => $this->erasmus->delete())->toThrow(ValidationException::class);

    $this->actingAs(superAdmin());
    expect(auth()->user()->can('delete', $this->erasmus))->toBeFalse()
        ->and(auth()->user()->can('delete', $this->spring))->toBeTrue();
});

it('shows the scholarships and their calls on the website, open ones first', function () {
    $this->get('/scholarships')->assertOk()
        ->assertSeeInOrder(['DIU Scholarship', 'Merit and need based.', 'Erasmus Scholarship', '2 calls and opportunities'])
        ->assertDontSee('Spring 2027 exchange at Lodz');

    $this->get($this->erasmus->url())->assertOk()
        ->assertSeeInOrder(['Scholarships', 'Erasmus Scholarship', 'Calls and opportunities', 'Spring 2027 exchange at Lodz', 'Open', 'Fall 2024 exchange at Halic', 'Closed'])
        ->assertSee('"@type":"MonetaryGrant"', false);

    $this->get($this->spring->url())->assertOk()
        ->assertSeeInOrder(['Erasmus Scholarship', 'Spring 2027 exchange at Lodz'])
        ->assertSee('https://apply.example.edu/lodz', false);
    $this->get($this->fall->url())->assertOk()->assertSee('Closed')
        ->assertDontSee('https://apply.example.edu/halic', false);                   // past the deadline: no apply button

    $old = $this->diu->url();
    $this->diu->changeSlug('en', 'diu-merit-scholarship');
    $this->get($old)->assertRedirect('/scholarships/diu-merit-scholarship');            // FR-GEN-32

    $this->erasmus->update(['is_active' => false]);
    $this->get($this->erasmus->url())->assertNotFound();
    $this->get($this->spring->url())->assertNotFound();                                 // its calls go with it
    $this->get('/scholarships/nothing-here')->assertNotFound();
});

it('groups scholarships by category on the website, with sub-categories in the filter (DB-03 §11.3 v1.4)', function () {
    Vocabularies::ensureTerms(Scholarship::CATEGORY, [
        ['name' => ['en' => 'International'], 'slug' => 'international', 'children' => [['name' => ['en' => 'Exchange'], 'slug' => 'exchange']]],
        ['name' => ['en' => 'Merit'], 'slug' => 'merit'],
    ]);
    $term = fn (string $name): int => (int) array_search($name, array_map(fn ($l) => ltrim($l, '— '), app(Vocabularies::class)->options(Scholarship::CATEGORY)), true);
    $this->diu->syncTerms(Scholarship::CATEGORY, [$term('Merit')]);
    $this->erasmus->syncTerms(Scholarship::CATEGORY, [$term('Exchange')]);
    $gks = Scholarship::factory()->create(['title' => ['en' => 'Korean Government Scholarship'], 'sort_order' => 3]);   // no category

    $this->get('/scholarships')->assertOk()
        ->assertSeeInOrder(['Exchange', 'Erasmus Scholarship', 'Merit', 'DIU Scholarship', 'Other scholarships', 'Korean Government Scholarship']);

    $this->get('/scholarships?category=international')->assertOk()             // a category with its sub-categories
        ->assertSee('Erasmus Scholarship')->assertDontSee('DIU Scholarship')->assertDontSee('Korean Government Scholarship');

    // Admin: the category is the scholarship's; its calls are found under it
    $this->actingAs(superAdmin());
    Livewire::test(ListScholarships::class)
        ->filterTable('category', $term('Exchange'))
        ->assertCanSeeTableRecords([$this->erasmus, $this->spring, $this->fall])->assertCanNotSeeTableRecords([$this->diu, $gks]);
    Livewire::withQueryParams(['parent' => $this->erasmus->id])->test(CreateScholarship::class)
        ->assertFormFieldIsHidden('category');                                    // a call has no category of its own
});

it('offers the listing to menus, answers a menu category link, and shows as an accordion with the settings text', function () {
    $links = app(MenuLinks::class);
    expect($links->modulePages())->toHaveKey('page:scholarship.index')
        ->and($links->modulePagePath('page:scholarship.index'))->toBe('/scholarships')
        ->and($links->modulePagePath('page:program.index'))->toBe('/programs');

    Vocabularies::ensureTerms(Scholarship::CATEGORY, [['name' => ['en' => 'Merit'], 'slug' => 'merit']]);
    $this->get('/scholarship/category/merit')->assertRedirect('/scholarships?category=merit');

    settings()->save('scholarship', ['intro' => ['en' => '<p>Waiver and scholarship schemes.</p>']], translatable: ['intro', 'outro']);
    settings()->save('layouts', ['scholarship.listing' => ['layout' => 'accordion']]);
    $this->get('/scholarships')->assertOk()
        ->assertSee('Waiver and scholarship schemes.')
        ->assertSee('<details class="accordion__item"', false);
});

it('shows scholarships on any page through the Scholarships block', function () {
    Vocabularies::ensureTerms(Scholarship::CATEGORY, [['name' => ['en' => 'Merit'], 'slug' => 'merit']]);
    $merit = (int) array_search('Merit', app(Vocabularies::class)->options(Scholarship::CATEGORY), true);
    $this->diu->syncTerms(Scholarship::CATEGORY, [$merit]);

    expect(ScholarshipsBlock::scholarships(['categories' => [$merit]])->pluck('id')->all())->toBe([$this->diu->id])
        ->and(ScholarshipsBlock::scholarships(['scholarships' => [$this->erasmus->id, $this->diu->id]])->pluck('id')->all())->toBe([$this->erasmus->id, $this->diu->id])
        ->and(ScholarshipsBlock::scholarships(['limit' => 1])->count())->toBe(1);

    $html = view(ScholarshipsBlock::view(), ['data' => ['categories' => [$merit], 'layout' => 'accordion'], 'locale' => 'en'])->render();
    expect($html)->toContain('DIU Scholarship')->not->toContain('Erasmus Scholarship')->toContain('accordion__item');
});

it('puts scholarships in search and the sitemap', function () {
    expect(collect(app(Sitemap::class)->entries())->pluck('loc')->implode(' '))
        ->toContain('/scholarships/'.$this->diu->slug('en'))
        ->toContain('/scholarships/'.$this->spring->slug('en'));

    expect($this->diu->toSearchDocument('en'))->toMatchArray(['title' => 'DIU Scholarship']);
});

it('gives the Waiver Office the scholarship permissions once, and nobody else', function () {
    $office = Role::findOrCreate('Waiver Office', 'web');
    (new ScholarshipDatabaseSeeder)->run();
    expect($office->fresh()->hasPermissionTo('Update:Scholarship'))->toBeTrue();

    $office->revokePermissionTo('Delete:Scholarship');
    (new ScholarshipDatabaseSeeder)->run();
    expect($office->fresh()->hasPermissionTo('Delete:Scholarship'))->toBeFalse();     // an Admin's change stays

    $this->actingAs(makeUser([]));   // no scholarship permission
    $this->get(ScholarshipResource::getUrl('index'))->assertForbidden();

    $user = makeUser([]);
    $user->assignRole('Waiver Office');
    $this->actingAs($user);
    $this->get(ScholarshipResource::getUrl('index'))->assertOk()->assertSee('Erasmus Scholarship');
});
