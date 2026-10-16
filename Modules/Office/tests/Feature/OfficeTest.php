<?php

/*
| M-12 Office completed (SRS FR-OFF-01..14, DB-04 §3): profile per language, type, own page
| or contact card, sections / services / support contacts with their people, /offices,
| /services, /support-contacts, the microsite home, counters, office users managing their own.
*/

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Office\Database\Seeders\OfficeDatabaseSeeder;
use Modules\Office\Filament\Resources\Offices\OfficeResource;
use Modules\Office\Filament\Resources\Offices\Pages\EditOffice;
use Modules\Office\Filament\Resources\Offices\RelationManagers\ServicesRelationManager;
use Modules\Office\Filament\Resources\Offices\RelationManagers\SupportContactsRelationManager;
use Modules\Office\Models\Office;
use Modules\Office\Models\OfficeService;
use Modules\Office\Models\OfficeSupportContact;
use Platform\Assignments\AssignmentEngine;
use Platform\Assignments\ManualPerson;
use Platform\Assignments\RoleCatalog;
use Platform\Modules\ModulePermissions;
use Platform\Permissions\EntityPermissions;
use Platform\Statistics\Statistics;
use Platform\Taxonomy\Vocabularies;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Office');
    app(RoleCatalog::class)->sync();
    (new OfficeDatabaseSeeder)->run();
    $type = fn (string $name): int => (int) array_search($name, app(Vocabularies::class)->options(Office::TYPE), true);

    $this->registrar = Office::create([
        'name' => ['en' => 'Registrar Office'], 'type_term_id' => $type('Administration'), 'overview' => ['en' => '<p>Records and certificates.</p>'],
        'email' => 'registrar@example.edu', 'phone' => '+880 1847-140000', 'location' => ['en' => 'Building 1, Level 3'], 'sort_order' => 1,
    ]);
    $this->transport = Office::create(['name' => ['en' => 'Transport'], 'type_term_id' => $type('Student Services'), 'has_microsite' => false, 'phone' => '+880 1700-000000', 'sort_order' => 2]);

    $this->transcript = OfficeService::create(['office_id' => $this->registrar->id, 'title' => ['en' => 'Transcript issue'], 'required_documents' => ['en' => '<p>Student ID card</p>'], 'fee_amount' => 500, 'processing_time' => ['en' => '7 working days']]);
    $this->idCard = OfficeSupportContact::create(['office_id' => $this->registrar->id, 'purpose' => ['en' => 'ID card problem after admission'], 'location' => ['en' => 'Room 301']]);
    $person = ManualPerson::create(['name' => ['en' => 'Ms. Akter'], 'designation' => ['en' => 'Officer'], 'phone' => '+8801700000001']);
    app(AssignmentEngine::class)->assign($person, $this->idCard, OfficeSupportContact::ROLE);
    app(AssignmentEngine::class)->assign($person, $this->transcript, OfficeService::ROLE);
    app(AssignmentEngine::class)->assign($person, $this->registrar, 'team');
});

it('lists offices by type, a contact card for one without its own page (FR-OFF-09/11)', function () {
    $this->get('/offices')->assertOk()
        ->assertSeeInOrder(['Administration', 'Registrar Office', 'Student Services', 'Transport'])
        ->assertSee($this->registrar->url(), false)
        ->assertSee('+880 1700-000000');

    expect($this->transport->url())->toBeNull();
    $this->get('/office/transport')->assertNotFound();
});

it('opens an office microsite with overview, services, team and contact (FR-OFF-10)', function () {
    $this->get($this->registrar->url())->assertOk()
        ->assertSeeInOrder(['Registrar Office', 'Records and certificates.', 'Services', 'Transcript issue', 'Student ID card', '7 working days', 'Our team', 'Ms. Akter', 'Building 1, Level 3', 'registrar@example.edu']);
});

it('shows every office\'s services and who to contact (FR-OFF-06/07)', function () {
    $this->get('/services')->assertOk()->assertSeeInOrder(['Registrar Office', 'Transcript issue', '500.00', 'Ms. Akter']);
    $this->get('/support-contacts')->assertOk()->assertSeeInOrder(['ID card problem after admission', 'Registrar Office', 'Room 301', 'Ms. Akter']);
});

it('gives a new office its own counters: services and team (FR-OFF-14)', function () {
    $stats = collect(app(Statistics::class)->forOwner($this->registrar->getMorphClass().':'.$this->registrar->id))->pluck('display', 'label');
    expect($stats->get('Services'))->toBe('1')->and($stats->get('Team members'))->toBe('1');
});

it('lets an office user manage their own office\'s services, not another\'s (BRL-OFF-03)', function () {
    $user = makeUser(['ViewAny:Office', 'View:Office', 'Update:Office', EntityPermissions::key('Manage', 'Office', $this->registrar->id)]);
    $this->actingAs($user);

    $this->get(OfficeResource::getUrl('edit', ['record' => $this->registrar]))->assertOk();
    Livewire::test(ServicesRelationManager::class, ['ownerRecord' => $this->registrar, 'pageClass' => EditOffice::class])
        ->assertSee('Transcript issue')
        ->callTableAction('create', data: ['title' => ['en' => 'Certificate verification'], 'is_active' => true])
        ->assertHasNoTableActionErrors();
    expect($this->registrar->services()->count())->toBe(2);

    expect(SupportContactsRelationManager::canViewForRecord($this->transport, EditOffice::class))->toBeFalse();
});

it('opens the office editor for an Admin with its lists', function () {
    $this->actingAs(superAdmin());
    $this->get(OfficeResource::getUrl('index'))->assertOk()->assertSee('Registrar Office');
    $this->get(OfficeResource::getUrl('edit', ['record' => $this->registrar]))->assertOk()
        ->assertSee('Services')->assertSee('Who to contact')->assertSee('Sections');
});
