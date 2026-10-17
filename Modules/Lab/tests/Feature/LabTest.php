<?php

/*
| Phase 3.30 — M-32 Lab / Research Center (DB-06 v1.3 §3, SRS Services FR-LAB-01..11): the lab
| microsite on its own or under its Department, its equipment / scopes / projects / products with
| shared categories and project pages, /labs with filters, its counters, content shown in it, and a
| lab user managing only their own lab.
*/

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Department\Models\Department;
use Modules\Facilities\Models\Facility;
use Modules\Faculty\Models\Faculty;
use Modules\Lab\Database\Seeders\LabDatabaseSeeder;
use Modules\Lab\Filament\Resources\Labs\LabResource;
use Modules\Lab\Filament\Resources\Labs\Pages\EditLab;
use Modules\Lab\Filament\Resources\Labs\RelationManagers\EquipmentRelationManager;
use Modules\Lab\Filament\Resources\Labs\RelationManagers\ProjectsRelationManager;
use Modules\Lab\Models\Lab;
use Modules\Lab\Models\LabItem;
use Platform\Assignments\AssignmentEngine;
use Platform\Assignments\ManualPerson;
use Platform\Assignments\RoleCatalog;
use Platform\Modules\ModulePermissions;
use Platform\Permissions\EntityPermissions;
use Platform\Relations\RelationEngine;
use Platform\Statistics\Statistics;
use Platform\Taxonomy\Vocabularies;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Lab');
    app(RoleCatalog::class)->sync();
    (new LabDatabaseSeeder)->run();
    $type = fn (string $name): int => (int) array_search($name, app(Vocabularies::class)->options(Lab::TYPE), true);
    $this->faculty = Faculty::factory()->create(['short_name' => 'FSIT']);
    $this->cse = Department::factory()->create(['faculty_id' => $this->faculty->id, 'short_name' => 'CSE']);

    $this->robotics = Lab::query()->create(['name' => ['en' => 'Robotics Lab'], 'department_id' => $this->cse->id, 'summary' => ['en' => 'Robots and drones.'], 'body' => ['en' => '<p>We build robots.</p>'], 'email' => 'robotics@example.edu', 'sort_order' => 1]);
    $this->robotics->syncTerms(Lab::TYPE, [$type('Research Lab')]);
    $this->center = Lab::query()->create(['name' => ['en' => 'Data Science Center'], 'sort_order' => 2]);
    $this->center->syncTerms(Lab::TYPE, [$type('Research Center')]);

    $this->arm = LabItem::query()->create(['lab_id' => $this->robotics->id, 'item_type' => 'equipment', 'title' => ['en' => 'Robot Arm'], 'quantity' => 4]);
    LabItem::query()->create(['lab_id' => $this->robotics->id, 'item_type' => 'equipment', 'title' => ['en' => 'Old Drone'], 'quantity' => 2, 'is_active' => false]);
    $this->rover = LabItem::query()->create(['lab_id' => $this->robotics->id, 'item_type' => 'project', 'title' => ['en' => 'Mars Rover'], 'student_names' => ['en' => 'Rafi, Nila'], 'description' => ['en' => '<p>A rover for rough ground.</p>']]);
    LabItem::query()->create(['lab_id' => $this->robotics->id, 'item_type' => 'scope', 'title' => ['en' => 'PCB design service']]);
    $person = ManualPerson::create(['name' => ['en' => 'Dr. Karim'], 'designation' => ['en' => 'Associate Professor']]);
    app(AssignmentEngine::class)->assign($person, $this->robotics, 'in_charge');
});

it('opens a lab microsite under its department or on its own, with items and project pages (FR-LAB-04/06/10)', function () {
    expect($this->robotics->url())->toBe(rtrim($this->cse->url(), '/').'/lab/robotics-lab')->and($this->center->url())->toBe('/lab/data-science-center');

    $this->get($this->robotics->url())->assertOk()
        ->assertSeeInOrder(['Research Lab', 'Robotics Lab', 'We build robots.', 'PCB design service', 'Robot Arm', 'Mars Rover', 'Dr. Karim', 'robotics@example.edu'])
        ->assertDontSee('Old Drone');
    $this->get('/lab/robotics-lab')->assertRedirect($this->robotics->url());                       // canonical under its department
    $this->get($this->center->url())->assertOk()->assertSee('Data Science Center');

    $this->get($this->robotics->url().'/equipment')->assertOk()->assertSee('Robot Arm')->assertSee(__('lab::lab.quantity', ['count' => 4]));
    expect($this->rover->url())->toBe($this->robotics->url().'/projects/mars-rover');
    $this->get($this->rover->url())->assertOk()->assertSee('A rover for rough ground.')->assertSee('Rafi, Nila');
    $this->get($this->robotics->url().'/equipment/robot-arm')->assertNotFound();                          // equipment has no page
    $this->get($this->center->url().'/projects/mars-rover')->assertNotFound();                            // another lab's
});

it('lists labs by type and department, counts their items, and shows content placed in a lab (FR-LAB-05/07/11)', function () {
    $this->get('/labs')->assertOk()->assertSeeInOrder(['Robotics Lab', 'Data Science Center']);
    $this->get('/labs?type=research-center')->assertOk()->assertSee('Data Science Center')->assertDontSee('Robotics Lab');
    $this->get('/labs?department='.$this->cse->id.'&view=list')->assertOk()->assertSee('Robotics Lab')->assertDontSee('Data Science Center');
    $this->get(rtrim($this->cse->url(), '/').'/labs')->assertOk()->assertSee('Robotics Lab')->assertDontSee('Data Science Center');

    $stats = collect(app(Statistics::class)->forOwner($this->robotics->getMorphClass().':'.$this->robotics->id))->pluck('display', 'label');
    expect($stats->get('Equipment'))->toBe('4')->and($stats->get('Projects'))->toBe('1');

    $room = Facility::factory()->create(['title' => ['en' => 'Clean Room']]);
    app(RelationEngine::class)->sync($room, [$this->robotics->getMorphClass().':'.$this->robotics->id], mainSite: false, user: null);
    $this->get($this->robotics->url().'/facilities')->assertOk()->assertSee('Clean Room');
});

it('lets a lab user manage their own lab\'s items, not another\'s (FR-LAB-03)', function () {
    $user = makeUser(['ViewAny:Lab', 'View:Lab', 'Update:Lab', EntityPermissions::key('Manage', 'Lab', $this->robotics->id)]);
    $this->actingAs($user);

    $this->get(LabResource::getUrl('edit', ['record' => $this->robotics]))->assertOk();
    Livewire::test(EquipmentRelationManager::class, ['ownerRecord' => $this->robotics, 'pageClass' => EditLab::class])
        ->assertSee('Robot Arm')->assertDontSee('Mars Rover')
        ->callTableAction('create', data: ['title' => ['en' => '3D Printer'], 'quantity' => 2, 'is_active' => true])
        ->assertHasNoTableActionErrors();
    expect($this->robotics->items()->ofType('equipment')->where('title->en', '3D Printer')->exists())->toBeTrue();
    expect(ProjectsRelationManager::canViewForRecord($this->center, EditLab::class))->toBeFalse();
});
