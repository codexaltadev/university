<?php

/*
| Phase 2.2 — installing and removing a module from the Module Manager screen
| with a real ZIP (runs real migrations, so it lives with the lifecycle tests).
*/

use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Modules\ModuleManager\Filament\Resources\Modules\Pages\ListModules;
use Platform\Models\CoreModule;
use Platform\Modules\ModuleRegistry;
use Tests\Support\LifecycleDatabase;
use Tests\Support\ModuleBuilder;

beforeEach(function () {
    config([
        'platform.modules.staging_path' => storage_path('framework/testing/module-packages'),
        'platform.modules.backup_path' => storage_path('framework/testing/module-backups'),
    ]);

    foreach (['modules-runtime', 'module-packages', 'module-backups', 'module-build'] as $dir) {
        File::deleteDirectory(storage_path("framework/testing/{$dir}"));
    }

    LifecycleDatabase::reset();
    app(ModuleRegistry::class)->refresh();
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

/** Livewire's upload testing needs a fake file; give it the real ZIP's bytes. */
function uploaded(string $zip): UploadedFile
{
    return UploadedFile::fake()->createWithContent('module.zip', (string) file_get_contents($zip));
}

it('installs a module from an uploaded ZIP, activates and removes it with its data', function () {
    $this->actingAs(superAdmin());

    Livewire::test(ListModules::class)
        ->callAction('installFromZip', data: ['package' => uploaded(ModuleBuilder::make('Sample')->buildZip())])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $row = CoreModule::where('slug', 'sample')->firstOrFail();
    expect($row->status)->toBe('installed')->and(Schema::hasTable('sample_items'))->toBeTrue();

    Livewire::test(ListModules::class)->callTableAction('activate', $row);
    expect(app(ModuleRegistry::class)->isActive('sample'))->toBeTrue();

    Livewire::test(ListModules::class)->callTableAction('deactivate', $row);
    Livewire::test(ListModules::class)->callTableAction('uninstall', $row, data: ['data' => 'delete', 'confirm' => 'sample']);

    expect(CoreModule::where('slug', 'sample')->exists())->toBeFalse()
        ->and(Schema::hasTable('sample_items'))->toBeFalse();
});

it('shows why a ZIP was refused and installs nothing', function () {
    $this->actingAs(superAdmin());

    Livewire::test(ListModules::class)
        ->callAction('installFromZip', data: ['package' => uploaded(ModuleBuilder::make('Sample', 'faq')->buildZip())])
        ->assertNotified(__('modulemanager::manager.errors.refused'));

    expect(CoreModule::where('name', 'Sample')->exists())->toBeFalse();
});
