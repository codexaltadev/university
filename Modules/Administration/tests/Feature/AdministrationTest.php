<?php

/*
| Phase 3.9 — M-13 Administration (DB-04 §4 v1.2, SRS People FR-ADMN-01..09): everyone but
| teachers in one person source; a category ladder with groups and directory visibility;
| /administration with filters and layouts, profile pages with roles; one phone number for
| every place (AC-ADMN-01); hidden contacts; leaving hides a person everywhere; import /
| export; Office users see their office's members.
*/

use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Administration\Filament\Resources\Members\MemberResource;
use Modules\Administration\Filament\Resources\Members\Pages\CreateMember;
use Modules\Administration\Filament\Resources\Members\Pages\ListMembers;
use Modules\Administration\Models\AdministrationCategory;
use Modules\Administration\Models\Member;
use Modules\Administration\Support\MemberSheet;
use Modules\Menu\Support\MenuLinks;
use Modules\Office\Models\Office;
use Platform\Assignments\AssignmentEngine;
use Platform\Assignments\RoleCatalog;
use Platform\Filament\Components\PersonPicker;
use Platform\Modules\ModulePermissions;
use Platform\Scope\UserScopeFilter;
use Platform\Seo\Sitemap;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    foreach (['Office', 'Administration'] as $module) {
        app(ModulePermissions::class)->sync($module);
    }
    app(RoleCatalog::class)->sync();   // Office roles: head, team

    $category = fn (string $name, string $group, int $order, bool $public = true) => AdministrationCategory::query()->create(['name' => ['en' => $name], 'group' => $group, 'sort_order' => $order, 'is_public' => $public, 'is_active' => true]);
    $this->management = $category('Management', 'management', 1);
    $this->director = $category('Director', 'management', 2);
    $this->officer = $category('Officer', 'officer', 3);
    $this->driver = $category('Driver', 'support', 4, false);

    $this->registrar = Office::create(['name' => ['en' => 'Registrar Office']]);
    $this->exam = Office::create(['name' => ['en' => 'Exam Office']]);

    $this->vc = Member::factory()->in($this->management)->create(['name' => ['en' => 'Prof. Rahman'], 'designation' => ['en' => 'Vice Chancellor']]);
    $this->dir = Member::factory()->in($this->director)->create(['name' => ['en' => 'Ms. Akter'], 'designation' => ['en' => 'Director (Finance)'], 'sort_order' => 2]);
    $this->dir2 = Member::factory()->in($this->director)->create(['name' => ['en' => 'Mr. Bari'], 'designation' => ['en' => 'Director (IT)'], 'sort_order' => 1]);
    $this->deputy = Member::factory()->in($this->officer)->create(['name' => ['en' => 'Mr. Karim'], 'designation' => ['en' => 'Deputy Registrar'],
        'phone' => '+880 1711 000000', 'extension' => '101', 'email' => 'karim@example.edu', 'primary_office_id' => $this->registrar->id]);
    $this->clerk = Member::factory()->in($this->officer)->create(['name' => ['en' => 'Ms. Nahar'], 'designation' => ['en' => 'Officer'], 'primary_office_id' => $this->exam->id]);
    $this->driverMember = Member::factory()->in($this->driver)->create(['name' => ['en' => 'Mr. Driver Alam'], 'designation' => ['en' => 'Driver']]);
});

it('keeps the category ladder sound and an employee ID one person\'s (FR-ADMN-02, BRL-ADMN-01)', function () {
    expect(fn () => AdministrationCategory::query()->create(['name' => ['en' => 'X'], 'group' => 'chief']))->toThrow(ValidationException::class)
        ->and(fn () => $this->officer->delete())->toThrow(ValidationException::class);

    $this->vc->update(['employee_id' => '710000001']);
    expect(fn () => $this->dir->update(['employee_id' => ' 710000001 ']))->toThrow(ValidationException::class);
    $this->dir->update(['employee_id' => '']);
    expect($this->dir->fresh()->employee_id)->toBeNull();
});

it('lists public categories in ladder order, filters, searches and switches layout (FR-ADMN-04, AC-ADMN-02, AC-ADMN-04)', function () {
    $this->get('/administration')->assertOk()
        ->assertSeeInOrder(['Management', 'Prof. Rahman', 'Director', 'Mr. Bari', 'Ms. Akter', 'Officer', 'Mr. Karim'])
        ->assertDontSee('Mr. Driver Alam');                                                           // support, not public

    $this->get('/administration?category='.$this->director->id)->assertOk()
        ->assertSeeInOrder(['Mr. Bari', 'Ms. Akter'])->assertDontSee('Prof. Rahman')->assertDontSee('Mr. Karim');
    $this->get('/administration?group=officer')->assertOk()->assertSee('Mr. Karim')->assertDontSee('Prof. Rahman');
    $this->get('/administration?office='.$this->exam->id)->assertOk()->assertSee('Ms. Nahar')->assertDontSee('Mr. Karim');
    $this->get('/administration?q=registrar')->assertOk()->assertSee('Mr. Karim')->assertDontSee('Ms. Nahar');
    $this->get('/administration?view=table')->assertOk()->assertSee('<table', false)->assertSee('karim@example.edu');
});

it('shows one phone number on every place the person holds, and their roles on the profile (AC-ADMN-01, AC-ADMN-03)', function () {
    $engine = app(AssignmentEngine::class);
    $engine->assign($this->deputy, $this->registrar, 'team', ['role_label' => ['en' => 'Deputy Registrar']]);
    $engine->assign($this->deputy, $this->exam, 'team', ['role_label' => ['en' => 'Member Secretary']]);

    $this->deputy->update(['phone' => '+880 1999 123456']);
    foreach ([$this->registrar, $this->exam] as $office) {
        expect($engine->people($office)->first()->phone())->toBe('+880 1999 123456');
    }
    $this->get($this->registrar->url())->assertOk()->assertSee('Mr. Karim');                        // the office's team

    $this->get($this->deputy->url())->assertOk()
        ->assertSee('Mr. Karim')->assertSee('Deputy Registrar')
        ->assertSeeInOrder(['Roles', 'Registrar Office', 'Deputy Registrar', 'Exam Office', 'Member Secretary'])
        ->assertSee('"@type":"Person"', false);
});

it('hides contacts that are not public, everywhere (FR-ADMN-07)', function () {
    app(AssignmentEngine::class)->assign($this->deputy, $this->registrar, 'team');
    $this->deputy->update(['show_phone' => false, 'show_extension' => false]);

    $person = app(AssignmentEngine::class)->people($this->registrar)->first();
    expect($person->phone())->toBeNull()->and($person->email())->toBe('karim@example.edu');
    $this->get('/administration')->assertOk()->assertDontSee('+880 1711 000000')->assertSee('karim@example.edu');
    $this->get($this->deputy->url())->assertOk()->assertDontSee('+880 1711 000000');
});

it('shows assigned support staff on their place, and hides people who left everywhere (BRL-ADMN-03, FR-ADMN-09)', function () {
    $engine = app(AssignmentEngine::class);
    $engine->assign($this->driverMember, $this->registrar, 'team');
    $engine->assign($this->deputy, $this->registrar, 'team');
    expect($engine->people($this->registrar)->map->name()->all())->toBe(['Mr. Driver Alam', 'Mr. Karim']);
    $this->get($this->driverMember->url())->assertNotFound();                                        // not in the directory: no profile

    $this->deputy->update(['is_active' => false]);
    expect($engine->people($this->registrar)->map->name()->all())->toBe(['Mr. Driver Alam']);
    $this->get('/administration')->assertDontSee('Mr. Karim');
    $this->get($this->deputy->url())->assertNotFound();
});

it('turns profile pages off from Settings', function () {
    settings()->save('administration', ['profile_pages' => false]);
    $this->get('/administration/'.$this->vc->slug())->assertNotFound();
    expect($this->vc->personPresenter()->profileUrl())->toBeNull()
        ->and($this->vc->url())->toStartWith('/administration?q=');
});

it('adds a member from the admin and offers members in the person picker (FR-ADMN-01, FR-REL-09)', function () {
    $this->actingAs(superAdmin());
    Livewire::test(CreateMember::class)
        ->fillForm(['name' => ['en' => 'Dr. Sultana'], 'designation' => ['en' => 'Controller of Examinations'], 'category_id' => $this->director->id,
            'primary_office_id' => $this->exam->id, 'email' => 'sultana@example.edu', 'employee_id' => '710009999'])
        ->call('create')->assertHasNoFormErrors();
    $sultana = Member::query()->where('employee_id', '710009999')->sole();
    expect($sultana->primary_office_id)->toBe($this->exam->id);

    expect(collect(PersonPicker::search('Sultana'))->keys()->all())->toContain(PersonPicker::keyOf($sultana));
});

it('imports and exports with the same columns, checking first (FR-ADMN-06)', function () {
    $this->deputy->update(['employee_id' => '710000101']);
    $file = UploadedFile::fake()->createWithContent('staff.csv', implode("\n", [
        'Employee ID,Name,Designation,Category,Office,Email,Phone,Extension,Active',
        '710000101,Mr. Karim,Deputy Registrar (Admin),Officer,Registrar Office,karim@example.edu,+880 1711 222222,101,yes',
        ',Ms. New,Assistant Officer,Officer,Exam Office,new@example.edu,,,yes',
        ',No Category,Clerk,Clerical,,,,,yes',
        ',Bad Email,Clerk,Officer,,not-an-email,,,yes',
    ]));
    $sheet = app(MemberSheet::class);
    $checked = $sheet->check($file->getRealPath(), 'csv');
    expect($checked['total'])->toBe(4)->and($checked['rows'])->toHaveCount(2)->and($checked['errors'])->toHaveCount(2);

    expect($sheet->save($checked['rows']))->toBe(['created' => 1, 'updated' => 1])
        ->and($this->deputy->fresh()->phone)->toBe('+880 1711 222222')
        ->and($this->deputy->fresh()->designation)->toBe('Deputy Registrar (Admin)')
        ->and(Member::query()->where('email', 'new@example.edu')->sole()->primary_office_id)->toBe($this->exam->id);

    $out = fopen('php://memory', 'w+');
    $sheet->export($out);
    rewind($out);
    $csv = stream_get_contents($out);
    expect($csv)->toStartWith('"Employee ID",Name,Designation,Category,Office,Email,Phone,Extension,Active')
        ->toContain('710000101,"Mr. Karim","Deputy Registrar (Admin)",Officer,"Registrar Office",karim@example.edu');
});

it('lets an Office user see and edit only their office\'s members (BRL-ADMN-02)', function () {
    $user = makeUser(['ViewAny:Member', 'View:Member', 'Update:Member'], [$this->registrar]);
    $this->actingAs($user);
    $this->get(MemberResource::getUrl('edit', ['record' => $this->clerk]))->assertNotFound();      // outside the scope: 404

    UserScopeFilter::enforce();   // as in the panel
    try {
        Livewire::test(ListMembers::class)->assertCanSeeTableRecords([$this->deputy])->assertCanNotSeeTableRecords([$this->clerk, $this->vc]);
        expect($user->can('update', $this->deputy))->toBeTrue();
    } finally {
        UserScopeFilter::enforce(false);
    }
});

it('offers the directory to menus and puts directory members in search and the sitemap', function () {
    expect(app(MenuLinks::class)->modulePagePath('page:administration.index'))->toBe('/administration');

    $sitemap = collect(app(Sitemap::class)->entries())->pluck('loc')->implode(' ');
    expect($sitemap)->toContain($this->vc->url())->not->toContain('/administration/'.$this->driverMember->slug())
        ->and($this->vc->toSearchDocument('en'))->toMatchArray(['title' => 'Prof. Rahman']);
});
