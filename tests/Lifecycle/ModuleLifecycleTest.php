<?php

/*
| Phase 1B — module lifecycle through the Module Manager (SAD Part A §10,
| Test Plan TC-MOD-01..12). Real migrations run, so each test resets the
| database itself (Tests\Support\LifecycleDatabase) instead of a transaction.
*/

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Platform\Models\CoreModule;
use Platform\Models\ModuleOperation;
use Platform\Modules\ModuleAutoloader;
use Platform\Modules\ModuleManager;
use Platform\Modules\ModuleOperationFailed;
use Platform\Modules\ModuleRegistry;
use Spatie\Permission\Models\Permission;
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
});

afterAll(function () {
    foreach (['modules-runtime', 'module-packages', 'module-backups', 'module-build'] as $dir) {
        File::deleteDirectory(storage_path("framework/testing/{$dir}"));
    }
});

function manager(): ModuleManager
{
    return app(ModuleManager::class);
}

function runtimePath(string $name = ''): string
{
    return rtrim(config('platform.modules.runtime_path'), '/').($name ? "/{$name}" : '');
}

/** @return list<string> errors of a refused operation */
function refused(Closure $operation): array
{
    try {
        $operation();
    } catch (ModuleOperationFailed $e) {
        return $e->errors;
    }

    test()->fail('The operation was expected to be refused.');
}

// ─── Full lifecycle ─────────────────────────────────────────────────────────

it('runs the full lifecycle from a ZIP: install → activate → deactivate → reactivate → uninstall (TC-MOD-01, 09, 10, 11)', function () {
    $zip = ModuleBuilder::make('Sample')->buildZip();

    // Install: tables, seeder, custom permission, status installed (inactive)
    $module = manager()->installZip($zip);

    expect($module->status)->toBe(CoreModule::STATUS_INSTALLED)
        ->and($module->is_bundled)->toBeFalse()
        ->and(Schema::hasTable('sample_items'))->toBeTrue()
        ->and(DB::table('sample_items')->count())->toBe(1)
        ->and(Permission::where('name', 'Import:SampleItem')->value('module_slug'))->toBe('sample')
        ->and(app(ModuleRegistry::class)->isActive('sample'))->toBeFalse()
        ->and(ModuleOperation::where('module_slug', 'sample')->value('status'))->toBe('success')
        ->and($module->versions()->value('to_version'))->toBe('1.0.0');

    // Activate: Shield resource permissions of this module
    manager()->activate('sample');

    expect(app(ModuleRegistry::class)->isActive('sample'))->toBeTrue()
        ->and(Permission::where('module_slug', 'sample')->where('kind', 'resource')->pluck('name')->all())
        ->toContain('ViewAny:SampleItem', 'Update:SampleItem');

    // Deactivate: data and permissions stay (FR-MODM-09)
    DB::table('sample_items')->insert(['name' => 'Mine', 'is_active' => true]);
    manager()->deactivate('sample');

    expect(app(ModuleRegistry::class)->isActive('sample'))->toBeFalse()
        ->and(DB::table('sample_items')->count())->toBe(2)
        ->and(Permission::where('name', 'Update:SampleItem')->exists())->toBeTrue();

    // Reactivate
    manager()->activate('sample');
    expect(app(ModuleRegistry::class)->isActive('sample'))->toBeTrue();

    // Uninstall needs an inactive module
    expect(refused(fn () => manager()->uninstall('sample')))->toContain('Deactivate the module before uninstalling it.');

    // Uninstall keeping data: code removed (backed up), tables stay
    manager()->deactivate('sample');
    manager()->uninstall('sample');

    expect(File::isDirectory(runtimePath('Sample')))->toBeFalse()
        ->and(glob(config('platform.modules.backup_path').'/Sample-1.0.0-*'))->toHaveCount(1)
        ->and(CoreModule::where('slug', 'sample')->exists())->toBeFalse()
        ->and(DB::table('sample_items')->count())->toBe(2);

    // Same slug installed again gets its data back
    manager()->installZip($zip);
    expect(DB::table('sample_items')->count())->toBe(2);

    // Uninstall with data: tables and permissions gone
    manager()->uninstall('sample', deleteData: true);

    expect(Schema::hasTable('sample_items'))->toBeFalse()
        ->and(Permission::where('module_slug', 'sample')->exists())->toBeFalse()
        ->and(File::isDirectory(runtimePath('Sample')))->toBeFalse();
});

// ─── ZIP validation (TC-MOD-02, 03) ────────────────────────────────────────

it('refuses unsafe or malformed ZIPs and leaves nothing behind', function (ModuleBuilder $builder, string $expected) {
    $errors = refused(fn () => manager()->installZip($builder->buildZip()));

    expect(implode("\n", $errors))->toContain($expected)
        ->and(glob(runtimePath('*')) ?: [])->toBe([])
        ->and(ModuleOperation::latest('id')->value('status'))->toBe('failed');
})->with([
    'path traversal' => [fn () => ModuleBuilder::make()->rawZipEntry('Sample/../../evil.php', '<?php'), 'Unsafe path'],
    'vendor folder' => [fn () => ModuleBuilder::make()->file('vendor/autoload.php', '<?php'), 'Folder not allowed'],
    'env file' => [fn () => ModuleBuilder::make()->file('.env', 'APP_KEY=x'), 'Environment files'],
    'executable type' => [fn () => ModuleBuilder::make()->file('public/shell.phtml', 'x'), 'File type not allowed'],
    'no module.json' => [fn () => ModuleBuilder::make()->file('module.json', null), 'Missing required file: Sample/module.json'],
    'no Extension' => [fn () => ModuleBuilder::make()->file('app/{{Name}}Extension.php', null), 'SampleExtension.php'],
    'composer require' => [fn () => ModuleBuilder::make()->file('composer.json', '{"require": {"guzzlehttp/guzzle": "^7"}}'), 'must not require packages'],
    'two root folders' => [fn () => ModuleBuilder::make()->rawZipEntry('Other/readme.txt'), 'exactly one module folder'],
    'core type' => [fn () => ModuleBuilder::make()->file('module.json', str_replace('"installable"', '"core"', file_get_contents(base_path('tests/Fixtures/module-template/module.json')))), 'must be of type "installable"'],
    'bad slug' => [fn () => ModuleBuilder::make('Sample', 'Bad_Slug'), 'uwms.slug must be'],
    'core version' => [fn () => ModuleBuilder::make()->file('module.json', str_replace('"^1.0"', '"^9.0"', file_get_contents(base_path('tests/Fixtures/module-template/module.json')))), 'Needs core ^9.0'],
]);

// ─── Conflicts (TC-MOD-04) ─────────────────────────────────────────────────

it('refuses a module whose slug, class names or tables clash with existing ones', function (ModuleBuilder $builder, string $expected) {
    $errors = refused(fn () => manager()->installZip($builder->buildZip()));

    expect(implode("\n", $errors))->toContain($expected)
        ->and(glob(runtimePath('*')) ?: [])->toBe([]);
})->with([
    'slug of a bundled module' => [fn () => ModuleBuilder::make('Sample', 'faq'), 'Slug "faq" is already used'],
    'resource class name' => [fn () => ModuleBuilder::make()->set('ResourceName', 'FaqResource'), 'Class name FaqResource is already used'],
    // Variants that change a migration use their own module name: Laravel's migrator
    // caches migration files by path for the lifetime of the PHP process.
    'table without prefix' => [fn () => ModuleBuilder::make('Badtable')->set('table', 'items'), 'Table items must start with "badtable_"'],
    'second table without prefix' => [fn () => ModuleBuilder::make('Twotables')->file('database/migrations/2026_01_03_000000_x.php', "<?php\nreturn new class extends Illuminate\\Database\\Migrations\\Migration { public function up(): void { Illuminate\\Support\\Facades\\Schema::create('news_posts', fn (\$t) => \$t->id()); } public function down(): void {} };"), 'Table news_posts must start with "twotables_"'],
    'table already exists' => [fn () => tap(ModuleBuilder::make('Existing'), fn () => Schema::create('existing_items', fn ($t) => $t->id())), 'Table existing_items already exists'],
    'migration fails in a dry run' => [fn () => ModuleBuilder::make('Dryfail')->file('database/migrations/2026_01_03_000000_broken.php', "<?php\nreturn new class extends Illuminate\\Database\\Migrations\\Migration { public function up(): void { throw new RuntimeException('boom'); } public function down(): void {} };"), 'fails in a dry run: boom'],
]);

// ─── Failure rollback (TC-MOD-08) ──────────────────────────────────────────

it('rolls back migrations, permissions and files when installing fails half-way', function () {
    // Passes the dry run (queries are only collected) but fails for real
    $zip = ModuleBuilder::make()
        ->file('database/migrations/2026_01_03_000000_broken.php', "<?php\nreturn new class extends Illuminate\\Database\\Migrations\\Migration { public function up(): void { Illuminate\\Support\\Facades\\DB::table('no_such_table')->insert(['x' => 1]); } public function down(): void {} };")
        ->buildZip();

    $errors = refused(fn () => manager()->installZip($zip));

    expect(implode(' ', $errors))->toContain('no_such_table')
        ->and(Schema::hasTable('sample_items'))->toBeFalse()
        ->and(Permission::where('name', 'Import:SampleItem')->exists())->toBeFalse()
        ->and(File::isDirectory(runtimePath('Sample')))->toBeFalse()
        ->and(CoreModule::where('slug', 'sample')->exists())->toBeFalse()
        ->and(ModuleOperation::latest('id')->value('status'))->toBe('rolled_back');
});

// ─── Dependencies (TC-MOD-06, 07) ──────────────────────────────────────────

it('enforces dependencies on install, activate, deactivate and uninstall', function () {
    expect(implode(' ', refused(fn () => manager()->installZip(ModuleBuilder::make('Addon')->dependsOn(['sample' => '^1.0'])->buildZip()))))
        ->toContain('Needs module "sample"');

    manager()->installZip(ModuleBuilder::make('Sample')->buildZip());
    manager()->installZip(ModuleBuilder::make('Addon')->dependsOn(['sample' => '^1.0'])->buildZip());

    expect(refused(fn () => manager()->activate('addon')))->toContain('Activate "sample" first.');

    manager()->activate('sample');
    manager()->activate('addon');

    expect(implode(' ', refused(fn () => manager()->deactivate('sample'))))->toContain('addon');

    manager()->deactivate('addon');
    manager()->deactivate('sample');

    expect(implode(' ', refused(fn () => manager()->uninstall('sample'))))->toContain('addon');
});

it('refuses a dependency whose installed version does not match', function () {
    manager()->installZip(ModuleBuilder::make('Sample')->buildZip());

    expect(implode(' ', refused(fn () => manager()->installZip(ModuleBuilder::make('Addon')->dependsOn(['sample' => '^2.0'])->buildZip()))))
        ->toContain('installed version is 1.0.0');
});

// ─── Manifest ↔ Extension (§10.2) ──────────────────────────────────────────

it('refuses to activate when module.json "provides" and the Extension disagree', function () {
    manager()->installZip(ModuleBuilder::make()->provides(['searchable' => true])->buildZip());

    expect(implode(' ', refused(fn () => manager()->activate('sample'))))
        ->toContain('declares "searchable" but the Extension does not register it');
});

// ─── Bundled modules ───────────────────────────────────────────────────────

it('records bundled modules with their default status, and keeps an uninstalled bundled module off', function () {
    expect(manager()->syncBundled())->toContain('faq', 'office')
        ->and(CoreModule::where('slug', 'faq')->value('status'))->toBe('active');

    manager()->deactivate('faq');
    manager()->uninstall('faq', deleteData: true);

    expect(Schema::hasTable('faq_faqs'))->toBeFalse()
        ->and(File::isDirectory(base_path('Modules/Faq')))->toBeTrue()          // bundled code stays
        ->and(CoreModule::where('slug', 'faq')->value('status'))->toBe('uninstalled')
        ->and(app(ModuleRegistry::class)->isActive('faq'))->toBeFalse();

    manager()->install(base_path('Modules/Faq'));
    expect(Schema::hasTable('faq_faqs'))->toBeTrue()
        ->and(CoreModule::where('slug', 'faq')->value('status'))->toBe('installed');
});

// ─── Scaffold (FR-MODM-15) ─────────────────────────────────────────────────

it('module:make-uwms creates a module that installs and activates cleanly', function () {
    $path = base_path('Modules/Zzscaffold');

    try {
        expect(Artisan::call('module:make-uwms', ['name' => 'Zzscaffold']))->toBe(0);

        app(ModuleAutoloader::class)->registerDirectory($path);
        app(ModuleRegistry::class)->refresh();

        $module = manager()->install($path);
        manager()->activate('zzscaffold');

        expect($module->slug)->toBe('zzscaffold')
            ->and(app(ModuleRegistry::class)->isActive('zzscaffold'))->toBeTrue();

        manager()->deactivate('zzscaffold');
    } finally {
        File::deleteDirectory($path);
        app(ModuleRegistry::class)->refresh();
    }
});
