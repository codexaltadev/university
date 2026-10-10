<?php

/*
| Academic › Waiver Calculator › Settings and the default roles (FR-ROLE-04 v1.1): the
| Waiver Office sets the rules and the calculator settings; the Admission Office the
| tuition fees. Both roles are made once by their module's seeder.
*/

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Tuition\Database\Seeders\TuitionDatabaseSeeder;
use Modules\Waiver\Database\Seeders\WaiverDatabaseSeeder;
use Modules\Waiver\Filament\Pages\CalculatorSettings;
use Modules\Waiver\Filament\Resources\WaiverRules\WaiverRuleResource;
use Platform\Modules\ModulePermissions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    foreach (['Program', 'Tuition', 'Waiver'] as $module) {
        app(ModulePermissions::class)->sync($module);
    }
    Permission::findOrCreate('Manage:WaiverSettings', 'web');
});

it('makes the Admission Office and Waiver Office roles once, and leaves an edited role alone', function () {
    (new TuitionDatabaseSeeder)->run();
    (new WaiverDatabaseSeeder)->run();

    $admission = Role::findByName('Admission Office', 'web');
    $waiver = Role::findByName('Waiver Office', 'web');
    expect($admission->hasPermissionTo('Update:Tuition'))->toBeTrue()
        ->and($admission->hasPermissionTo('Update:WaiverRule'))->toBeFalse()
        ->and($waiver->hasPermissionTo('Update:WaiverRule'))->toBeTrue()
        ->and($waiver->hasPermissionTo('Manage:WaiverSettings'))->toBeTrue()
        ->and($waiver->hasPermissionTo('Update:Tuition'))->toBeFalse()
        ->and($waiver->hasPermissionTo('View:Tuition'))->toBeTrue();

    $waiver->revokePermissionTo('Delete:Board');
    (new WaiverDatabaseSeeder)->run();
    expect($waiver->fresh()->hasPermissionTo('Delete:Board'))->toBeFalse();
});

it('lets the Waiver Office change the calculator settings in its menu', function () {
    (new WaiverDatabaseSeeder)->run();
    $office = makeUser([]);
    $office->assignRole('Waiver Office');
    $this->actingAs($office);

    $this->get(WaiverRuleResource::getUrl('index'))->assertOk()
        ->assertSee(CalculatorSettings::getUrl(), false)
        ->assertSee(url('/waiver-calculator'), false);   // Preview

    Livewire::test(CalculatorSettings::class)
        ->assertSet('data.waiver_mode', 'highest')
        ->set('data.waiver_mode', 'additive')
        ->set('data.disclaimer.en', 'Figures are estimates.')
        ->call('save')
        ->assertHasNoErrors();
    expect(settings('waiver.waiver_mode'))->toBe('additive')
        ->and(settings('waiver.disclaimer'))->toBe('Figures are estimates.');

    $this->actingAs(makeUser(['ViewAny:WaiverRule']));
    $this->get(CalculatorSettings::getUrl())->assertForbidden();
});
