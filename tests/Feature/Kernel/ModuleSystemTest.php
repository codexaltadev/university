<?php

/*
| Phase 1B — manifest, database activator, Extension registry (SAD Part A §5, §7, ADR-02).
*/

use App\Models\User;
use App\Providers\Filament\AdminPanelProvider;
use Modules\Faq\Filament\FaqPlugin;
use Modules\Office\Filament\OfficePlugin;
use Modules\Office\Models\Office;
use Nwidart\Modules\Facades\Module;
use Platform\Models\CoreModule;
use Platform\Modules\Manifest;
use Platform\Modules\ManifestValidator;
use Platform\Modules\ModulePermissions;
use Platform\Modules\ModuleRegistry;
use Platform\Registry\ExtensionLoader;
use Platform\Registry\Registry;
use Spatie\Permission\Models\Permission;

function manifestWith(array $uwms): Manifest
{
    $base = json_decode(file_get_contents(base_path('Modules/Faq/module.json')), true);
    $base['uwms'] = array_replace($base['uwms'], $uwms);

    return new Manifest($base, base_path('Modules/Faq'));
}

// ─── Manifest ──────────────────────────────────────────────────────────────

it('accepts the bundled manifests', function (string $module) {
    expect(app(ManifestValidator::class)->validate(Manifest::fromDirectory(base_path("Modules/{$module}"))))->toBe([]);
})->with(['Faq', 'Office']);

it('rejects invalid manifest fields with a readable reason', function (array $uwms, string $expected) {
    expect(implode("\n", app(ManifestValidator::class)->validate(manifestWith($uwms))))->toContain($expected);
})->with([
    'slug' => [['slug' => 'Faq Module'], 'uwms.slug must be'],
    'version' => [['version' => '1.0'], 'semantic version'],
    'type' => [['type' => 'plugin'], 'uwms.type must be'],
    'core constraint' => [['requires_core' => 'latest'], 'version constraint'],
    'core version' => [['requires_core' => '^2.0'], 'Needs core ^2.0'],
    'table prefix' => [['table_prefix' => 'faqs_'], 'uwms.table_prefix must be "faq_"'],
    'dependency constraint' => [['dependencies' => ['faculty' => 'any']], 'invalid version constraint'],
    'morph alias' => [['morph_aliases' => ['question' => 'Modules\\Faq\\Models\\Faq']], 'must start with "faq."'],
    'morph class' => [['morph_aliases' => ['faq.faq' => 'App\\Models\\User']], 'must point to a class of this module'],
    'provides key' => [['provides' => ['magic' => true]], 'Unknown uwms.provides key "magic"'],
]);

it('kebab slugs map to underscore table prefixes', function () {
    $manifest = manifestWith(['slug' => 'faculty-member', 'table_prefix' => 'faculty_member_']);

    expect($manifest->expectedTablePrefix())->toBe('faculty_member_')
        ->and(app(ManifestValidator::class)->validate($manifest))->not->toContain('uwms.table_prefix must be "faculty_member_".');
});

// ─── Database activator (ADR-02) ───────────────────────────────────────────

it('without a row, bundled modules follow default_active', function () {
    expect(CoreModule::count())->toBe(0)
        ->and(Module::find('Faq')->isEnabled())->toBeTrue();
});

it('stores status changes in core_modules, not in a file', function () {
    Module::find('Faq')->disable();

    expect(CoreModule::where('slug', 'faq')->value('status'))->toBe('installed')
        ->and(app(ModuleRegistry::class)->isActive('faq'))->toBeFalse()
        ->and(file_exists(base_path('modules_statuses.json')))->toBeFalse();

    Module::find('Faq')->enable();

    expect(CoreModule::where('slug', 'faq')->value('status'))->toBe('active')
        ->and(app(ModuleRegistry::class)->isActive('faq'))->toBeTrue();
});

it('finds modules and owners by slug', function () {
    expect(app(ModuleRegistry::class)->find('office')?->getName())->toBe('Office')
        ->and(app(ModuleRegistry::class)->slugForClass(Office::class))->toBe('office')
        ->and(app(ModuleRegistry::class)->slugForClass(User::class))->toBeNull();
});

// ─── Extension registry (§7) ───────────────────────────────────────────────

it('active modules register through their Extension', function () {
    $registry = app(Registry::class);

    expect(collect($registry->all('filamentPlugin'))->pluck('class')->all())
        ->toContain(FaqPlugin::class, OfficePlugin::class)
        ->and(collect($registry->all('dynamicPermissions'))->pluck('model')->all())
        ->toBe([Office::class]);
});

it('inactive modules register nothing, and their panel plugin disappears', function () {
    Module::find('Office')->disable();
    app(ExtensionLoader::class)->reload();

    expect(collect(app(Registry::class)->all('filamentPlugin'))->pluck('class')->all())
        ->toContain(FaqPlugin::class)
        ->not->toContain(OfficePlugin::class);

    $plugins = (fn () => $this->activeModulePlugins())->call(new AdminPanelProvider(app()));
    expect(array_map('get_class', $plugins))->not->toContain(OfficePlugin::class);
});

it('bundled manifests match what their Extensions register', function (string $module) {
    expect(app(ExtensionLoader::class)->mismatches(Manifest::fromDirectory(base_path("Modules/{$module}"))))->toBe([]);
})->with(['Faq', 'Office']);

// ─── Kernel permissions ────────────────────────────────────────────────────

it('creates permissions for kernel resources (Role editor)', function () {
    app(ModulePermissions::class)->syncKernel();

    expect(Permission::where('name', 'Update:Role')->first())
        ->not->toBeNull()
        ->module_slug->toBeNull()
        ->kind->toBe('resource');
});
