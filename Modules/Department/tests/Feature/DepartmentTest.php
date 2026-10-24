<?php

/*
| Phase 3.2 — Department (M-02): admin, Faculty required, short name unique per
| Faculty, profile-only edit for scoped users, moving to another Faculty, delete
| guard, counters, website listing grouped by Faculty, external link, microsite
| home, homepage section, search and sitemap. FR-DEPT-01..09, BRL-DEPT-01..03,
| AC-DEPT-01/02.
*/

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Modules\Department\Filament\Resources\Departments\DepartmentResource;
use Modules\Department\Filament\Resources\Departments\Pages\CreateDepartment;
use Modules\Department\Filament\Resources\Departments\Pages\EditDepartment;
use Modules\Department\Models\Department;
use Modules\Department\Sections\DepartmentsSection;
use Modules\Faculty\Models\Faculty;
use Modules\Menu\Support\MenuEditor;
use Modules\Menu\Support\MenuTree;
use Platform\Assignments\ManualPerson;
use Platform\Assignments\RoleCatalog;
use Platform\Context\SiteContext;
use Platform\Events\EntityHierarchyChanged;
use Platform\Exceptions\ParentHasActiveChildren;
use Platform\Homepage\SectionContext;
use Platform\Modules\ModulePermissions;
use Platform\Registry\Registry;
use Platform\Scope\ScopeResolver;
use Platform\Search\SearchIndex;
use Platform\Statistics\StatContext;
use Platform\Statistics\Statistics;
use Platform\Statistics\StatSet;
use Platform\Theme\LayoutResolver;
use Tests\Fixtures\Models\FixtureChild;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Faculty');
    app(ModulePermissions::class)->sync('Department');

    $this->fsit = Faculty::factory()->create(['name' => ['en' => 'Faculty of Science and Information Technology'], 'short_name' => 'FSIT']);
    $this->fe = Faculty::factory()->create(['name' => ['en' => 'Faculty of Engineering'], 'short_name' => 'FE']);

    $this->cse = Department::factory()->create([
        'faculty_id' => $this->fsit->id,
        'name' => ['en' => 'Department of Computer Science and Engineering', 'bn' => 'কম্পিউটার বিজ্ঞান ও প্রকৌশল বিভাগ'],
        'short_name' => 'CSE',
        'about_title' => ['en' => 'Why CSE at DIU'],
        'about' => ['en' => '<p>Software, systems and AI.</p>'],
        'head_message' => ['en' => '<p>Welcome to CSE.</p>'],
    ]);
});

it('creates a department under a Faculty with its address and its own counters (FR-DEPT-01/05)', function () {
    $this->actingAs(superAdmin());

    // A Faculty is required
    Livewire::test(CreateDepartment::class)
        ->fillForm(['name' => ['en' => 'Department of Software Engineering'], 'short_name' => 'SWE'])
        ->call('create')
        ->assertHasFormErrors(['faculty_id' => 'required']);

    Livewire::test(CreateDepartment::class)
        ->fillForm(['faculty_id' => $this->fsit->id, 'name' => ['en' => 'Department of Software Engineering'], 'short_name' => 'SWE', 'web_address' => 'swe'])
        ->call('create')
        ->assertHasNoFormErrors();

    $swe = Department::query()->where('short_name', 'SWE')->sole();
    expect($swe->url())->toBe('/'.$this->fsit->slug('en').'/swe')   // under its Faculty (FR-GEN-34)
        ->and($swe->faculty->is($this->fsit))->toBeTrue();

    $set = StatSet::query()->ownedBy($swe->getMorphClass().':'.$swe->id)->sole();
    expect($set->items()->pluck('metric_key')->filter()->values()->all())->toBe(['program.count', 'faculty-member.count']);
});

it('keeps its Quick Links, entered with the department, in their order', function () {
    $this->actingAs(superAdmin());
    Livewire::test(EditDepartment::class, ['record' => $this->cse->getRouteKey()])
        ->fillForm(['quick_links' => [
            ['label' => ['en' => 'Admission Requirements'], 'url' => '/admission'],
            ['label' => ['en' => 'Student Portal', 'bn' => 'স্টুডেন্ট পোর্টাল'], 'url' => 'https://studentportal.example.edu'],
        ]])
        ->call('save')->assertHasNoFormErrors();

    expect(link_list($this->cse->fresh()->quick_links))->toBe([
        ['label' => 'Admission Requirements', 'url' => url('/admission')],
        ['label' => 'Student Portal', 'url' => 'https://studentportal.example.edu'],
    ]);
    app()->setLocale('bn');
    expect(link_list($this->cse->fresh()->quick_links)[1]['label'])->toBe('স্টুডেন্ট পোর্টাল')
        ->and(link_list($this->cse->fresh()->quick_links)[0]['label'])->toBe('Admission Requirements');   // no Bangla: English
});

it('keeps the short name unique inside a Faculty only (BRL-DEPT-01)', function () {
    $this->actingAs(superAdmin());

    Livewire::test(CreateDepartment::class)
        ->fillForm(['faculty_id' => $this->fsit->id, 'name' => ['en' => 'Another CSE'], 'short_name' => 'CSE', 'web_address' => 'cse-2'])
        ->call('create')
        ->assertHasFormErrors(['short_name']);

    Livewire::test(CreateDepartment::class)
        ->fillForm(['faculty_id' => $this->fe->id, 'name' => ['en' => 'Department of Civil Engineering'], 'short_name' => 'CSE', 'web_address' => 'fe-cse'])
        ->call('create')
        ->assertHasNoFormErrors();

    // A deleted one frees its short name
    $this->cse->delete();
    Livewire::test(CreateDepartment::class)
        ->fillForm(['faculty_id' => $this->fsit->id, 'name' => ['en' => 'New CSE'], 'short_name' => 'CSE', 'web_address' => 'new-cse'])
        ->call('create')
        ->assertHasNoFormErrors();
});

it('lets a Department user edit only their profile, other departments answer 404 (AC-DEPT-01)', function () {
    $eee = Department::factory()->create(['faculty_id' => $this->fe->id, 'short_name' => 'EEE']);
    $user = makeUser(['ViewAny:Department', 'View:Department', 'Update:Department'], [$this->cse]);
    $this->actingAs($user);

    $this->get(DepartmentResource::getUrl('edit', ['record' => $eee]))->assertNotFound();
    $this->get(DepartmentResource::getUrl('edit', ['record' => $this->cse]))->assertOk();

    Livewire::test(EditDepartment::class, ['record' => $this->cse->getRouteKey()])
        ->assertFormFieldIsDisabled('faculty_id')
        ->assertFormFieldIsDisabled('short_name')
        ->assertFormFieldIsDisabled('web_address')
        ->fillForm(['about' => ['en' => '<p>New about.</p>'], 'phone' => '+880 5678'])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->cse->refresh();
    expect($this->cse->faculty_id)->toBe($this->fsit->id)
        ->and($this->cse->short_name)->toBe('CSE')
        ->and($this->cse->phone)->toBe('+880 5678')
        ->and($this->cse->getTranslation('about', 'en'))->toContain('New about.');
});

it('gives a Faculty user the departments of their Faculty; creating one needs the permission (BRL-DEPT-03)', function () {
    $eee = Department::factory()->create(['faculty_id' => $this->fe->id, 'short_name' => 'EEE']);

    $reader = makeUser(['ViewAny:Department', 'View:Department', 'Update:Department'], [$this->fsit]);
    $this->actingAs($reader);
    $this->get(DepartmentResource::getUrl())->assertOk()->assertSee('CSE')->assertDontSee('EEE');
    $this->get(DepartmentResource::getUrl('edit', ['record' => $this->cse]))->assertOk();
    $this->get(DepartmentResource::getUrl('edit', ['record' => $eee]))->assertNotFound();
    $this->get(DepartmentResource::getUrl('create'))->assertForbidden();

    // With Create:Department, only into their own Faculty
    $creator = makeUser(['ViewAny:Department', 'View:Department', 'Create:Department'], [$this->fsit]);
    $this->actingAs($creator);

    Livewire::test(CreateDepartment::class)
        ->fillForm(['faculty_id' => $this->fe->id, 'name' => ['en' => 'Sneaky'], 'short_name' => 'SNK'])
        ->call('create')
        ->assertHasFormErrors(['faculty_id']);

    Livewire::test(CreateDepartment::class)
        ->fillForm(['faculty_id' => $this->fsit->id, 'name' => ['en' => 'Department of Multimedia'], 'short_name' => 'MCT', 'web_address' => 'mct'])
        ->call('create')
        ->assertHasNoFormErrors();
});

it('moves to another Faculty: scope and menu fallback follow (FR-DEPT-08, AC-DEPT-02)', function () {
    $k = fn ($m) => $m->getMorphClass().':'.$m->id;
    $editor = app(MenuEditor::class);
    foreach ([[$k($this->fsit), 'FSIT Home'], [$k($this->fe), 'FE Home']] as [$owner, $title]) {
        $editor->save($editor->create($owner, 'primary'), [['type' => 'url', 'url' => '/x', 'title' => ['en' => $title]]]);
    }

    $menu = fn () => array_column(app(MenuTree::class)->forLocation('primary', SiteContext::for($this->cse->fresh())), 'title');
    expect($menu())->toBe(['FSIT Home']);

    $feUser = makeUser(['ViewAny:Department', 'View:Department'], [$this->fe]);
    expect(app(ScopeResolver::class)->allows($feUser, $this->cse))->toBeFalse();

    $moves = [];
    Event::listen(function (EntityHierarchyChanged $e) use (&$moves): void {
        $moves[] = [$e->entity->getKey(), $e->fromParent, $e->toParent];
    });

    $this->actingAs(superAdmin());
    Livewire::test(EditDepartment::class, ['record' => $this->cse->getRouteKey()])
        ->fillForm(['faculty_id' => $this->fe->id])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($moves)->toBe([[$this->cse->id, $k($this->fsit), $k($this->fe)]]);

    expect($menu())->toBe(['FE Home'])
        ->and(app(ScopeResolver::class)->allows($feUser, $this->cse->fresh()))->toBeTrue()
        ->and($this->cse->fresh()->ancestorKeys())->toBe([$k($this->fe)]);
});

it('cannot be deleted while it has active children, and blocks deleting its Faculty (BRL-DEPT-02, BRL-FAC-01)', function () {
    try {
        $this->fsit->delete();
        $this->fail('The faculty was deleted.');
    } catch (ParentHasActiveChildren $e) {
        expect($e->children)->toBe(['departments' => ['Department of Computer Science and Engineering']]);
    }

    // Programs / Faculty Members register as children the same way
    app(Registry::class)->add('relationTarget', 'test', ['model' => FixtureChild::class, 'parent' => Department::class, 'parentKey' => 'parent_id']);
    Schema::disableForeignKeyConstraints();
    FixtureChild::create(['parent_id' => $this->cse->id, 'name' => 'BSc in CSE', 'code' => 'BSC']);
    Schema::enableForeignKeyConstraints();

    expect(fn () => $this->cse->delete())->toThrow(ParentHasActiveChildren::class);
});

it('counts departments for the main site and per Faculty (AC-FAC-02)', function () {
    Department::factory()->count(2)->create(['faculty_id' => $this->fsit->id]);
    Department::factory()->create(['faculty_id' => $this->fe->id]);
    Department::factory()->create(['faculty_id' => $this->fsit->id, 'is_active' => false]);

    $count = fn (string $owner) => app(Statistics::class)->metric('department.count', StatContext::of($owner));

    expect($count('site:0'))->toBe(4)
        ->and($count($this->fsit->getMorphClass().':'.$this->fsit->id))->toBe(3);

    $faculty = collect(app(Statistics::class)->forOwner($this->fsit->getMorphClass().':'.$this->fsit->id))->firstWhere('label', 'Departments');
    expect($faculty['display'])->toBe('3');
});

it('lists departments on /departments grouped by Faculty, with filter, layouts and external links (FR-DEPT-04/06/09)', function () {
    $eee = Department::factory()->create(['faculty_id' => $this->fe->id, 'name' => ['en' => 'Department of Electrical Engineering'], 'short_name' => 'EEE']);
    $ext = Department::factory()->create(['faculty_id' => $this->fe->id, 'name' => ['en' => 'Department of Architecture'], 'external_url' => 'https://arch.example.edu']);
    $hidden = Department::factory()->create(['faculty_id' => $this->fsit->id, 'name' => ['en' => 'Department of Hidden Things'], 'show_in_listing' => false]);

    $this->get('/departments')->assertOk()
        ->assertSeeInOrder(['Faculty of Science and Information Technology', 'Department of Computer Science and Engineering', 'Faculty of Engineering', 'Department of Electrical Engineering'])
        ->assertSee('department-card--grid', false)
        ->assertSee('href="https://arch.example.edu"', false)
        ->assertDontSee('Department of Hidden Things');

    $this->get('/departments?faculty=fe')->assertOk()
        ->assertSee('Department of Electrical Engineering')
        ->assertDontSee('Department of Computer Science and Engineering');

    app(LayoutResolver::class)->choose('department.listing', 'list');
    $this->get('/departments')->assertSee('department-card--list', false)->assertSee('Software, systems and AI.');

    // Hidden keeps its address; departments of an inactive Faculty leave the list
    $this->get($hidden->url())->assertOk();
    $this->fe->update(['is_active' => false]);
    $this->get('/departments')->assertDontSee('Department of Electrical Engineering');
});

it('shows a default microsite home with about, Head and counters (FR-DEPT-03/07)', function () {
    app(RoleCatalog::class)->sync();
    $head = ManualPerson::create(['name' => ['en' => 'Dr. Sheak Rashed Haider Noori'], 'designation' => ['en' => 'Head, CSE']]);
    $this->cse->assignments()->create(['person_type' => $head->getMorphClass(), 'person_id' => $head->id, 'role_key' => 'head', 'sort_order' => 1, 'is_public' => true]);

    $students = StatSet::query()->ownedBy($this->cse->getMorphClass().':'.$this->cse->id)->sole()->items()->where('source', 'manual')->sole();
    $students->update(['manual_value' => 3500]);

    $this->get($this->cse->url())->assertOk()
        ->assertSee('Department of Computer Science and Engineering')
        ->assertSee('Why CSE at DIU')
        ->assertSee('Software, systems and AI.')
        ->assertSee('Welcome to CSE.')
        ->assertSee('Dr. Sheak Rashed Haider Noori')
        ->assertSee('3,500+')
        ->assertSee($this->fsit->url(), false)                    // back to its Faculty
        ->assertSee('microsite-name', false);

    $this->get('/bn'.$this->cse->url('bn'))->assertOk()->assertSee('কম্পিউটার বিজ্ঞান ও প্রকৌশল বিভাগ');
});

it('offers the "Departments" homepage section for the main site and a Faculty microsite, search and sitemap (FR-DEPT-09)', function () {
    $eee = Department::factory()->create(['faculty_id' => $this->fe->id, 'short_name' => 'EEE']);
    $section = new DepartmentsSection;

    expect($section->items(new SectionContext('site:0', [], 'en'))->pluck('short_name')->all())->toContain('CSE', 'EEE')
        ->and($section->items(new SectionContext('site:0', ['faculty_id' => $this->fe->id], 'en'))->pluck('short_name')->all())->toBe(['EEE'])
        ->and($section->items(new SectionContext($this->fsit->getMorphClass().':'.$this->fsit->id, [], 'en'))->pluck('short_name')->all())->toBe(['CSE']);

    expect(app(SearchIndex::class)->search('CSE')['groups'])->toHaveKey('department');

    $this->get('/sitemap.xml')->assertSee(url($this->cse->url()), false);
});

it('lets only permitted users into the department screens', function () {
    $this->actingAs(makeUser());
    $this->get(DepartmentResource::getUrl())->assertForbidden();

    $this->actingAs(superAdmin());
    $this->get(DepartmentResource::getUrl())->assertOk()->assertSee('CSE')->assertSee('FSIT');
});
