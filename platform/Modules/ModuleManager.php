<?php

namespace Platform\Modules;

use Composer\Semver\Semver;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\Facades\Module;
use Platform\Assignments\RoleCatalog;
use Platform\Backup\Backups;
use Platform\Events\ModuleActivated;
use Platform\Events\ModuleDeactivated;
use Platform\Events\ModuleInstalled;
use Platform\Events\ModuleUninstalled;
use Platform\Events\ModuleUninstalling;
use Platform\Models\CoreModule;
use Platform\Models\ModuleOperation;
use Platform\Registry\ExtensionLoader;
use Platform\Registry\Registry;
use Throwable;

/**
 * Module lifecycle (SAD Part A §10, SRS C-01): install (folder or ZIP),
 * activate, deactivate, uninstall. Every run is logged step by step and
 * undone on failure (FR-MODM-06).
 */
class ModuleManager
{
    public function __construct(
        protected ModuleRegistry $modules,
        protected ManifestValidator $validator,
        protected ConflictChecker $conflicts,
        protected ModulePermissions $permissions,
        protected ExtensionLoader $extensions,
        protected ModuleAutoloader $autoloader,
    ) {}

    // ── Install ──────────────────────────────────────────────────────────────

    /** Install from an uploaded ZIP (FR-MODM-02/03). Always ends inactive (FR-MODM-16). */
    public function installZip(string $zipPath): CoreModule
    {
        $package = new ZipPackage($zipPath);
        $op = ModuleOperation::start(basename($zipPath), 'install');

        if ($errors = $package->inspect()) {
            return $this->fail($op, 'install', $errors);
        }
        $op->step('package checked');

        $staged = $package->extractToStaging();

        try {
            $manifest = Manifest::fromDirectory($staged);
        } catch (Throwable $e) {
            File::deleteDirectory(dirname($staged));

            return $this->fail($op, 'install', [$e->getMessage()]);
        }

        $op->update(['module_slug' => $manifest->slug() ?: basename($zipPath)]);

        if ($errors = $this->validator->validate($manifest, fromZip: true)) {
            File::deleteDirectory(dirname($staged));

            return $this->fail($op, 'install', $errors);
        }

        if ($manifest->name() !== $package->rootFolder()) {
            File::deleteDirectory(dirname($staged));

            return $this->fail($op, 'install', ["Root folder must be named {$manifest->name()}."]);
        }

        $target = rtrim(config('platform.modules.runtime_path'), '/').'/'.$manifest->name();

        if (file_exists($target)) {
            File::deleteDirectory(dirname($staged));

            return $this->fail($op, 'install', ["A module folder {$manifest->name()} already exists."]);
        }

        File::ensureDirectoryExists(dirname($target));
        File::moveDirectory($staged, $target);
        File::deleteDirectory(dirname($staged));
        $op->step('moved to modules folder', message: $target);

        return $this->runInstall(Manifest::fromDirectory($target), $op, removeFolderOnFailure: true);
    }

    /** Install a module folder that is already in place (bundled or runtime). */
    public function install(string $directory): CoreModule
    {
        $manifest = Manifest::fromDirectory($directory);
        $op = ModuleOperation::start($manifest->slug(), 'install');

        if ($errors = $this->validator->validate($manifest)) {
            return $this->fail($op, 'install', $errors);
        }

        return $this->runInstall($manifest, $op, removeFolderOnFailure: false);
    }

    protected function runInstall(Manifest $manifest, ModuleOperation $op, bool $removeFolderOnFailure): CoreModule
    {
        $existing = CoreModule::query()->where('slug', $manifest->slug())->first();

        if ($existing && $existing->status !== CoreModule::STATUS_UNINSTALLED) {
            return $this->undoInstall($manifest, $op, ["Module \"{$manifest->slug()}\" is already installed."], $removeFolderOnFailure, migrated: false);
        }

        $this->autoloader->registerDirectory($manifest->path);
        $this->modules->refresh();

        $errors = [...$this->conflicts->check($manifest), ...$this->missingDependencies($manifest)];
        if ($errors) {
            return $this->undoInstall($manifest, $op, $errors, $removeFolderOnFailure, migrated: false);
        }
        $op->step('conflicts and dependencies checked');

        // FR-BKP-03 / BR-MOD-07: database backup first; a failed backup stops the install
        $backup = app(Backups::class)->beforeChange("install {$manifest->slug()} {$manifest->version()}");
        $op->step('database backup', $backup['status'] === 'success' ? 'ok' : $backup['status'], $backup['message']);

        if ($backup['status'] === 'failed') {
            return $this->undoInstall($manifest, $op, ['Database backup failed: '.$backup['message']], $removeFolderOnFailure, migrated: false);
        }

        try {
            if (is_dir($manifest->migrationsPath())) {
                Artisan::call('migrate', ['--path' => $manifest->migrationsPath(), '--realpath' => true, '--force' => true]);
                $op->step('migrations');
            }

            $this->permissions->syncCustom($manifest);
            $op->step('custom permissions');

            if (class_exists($manifest->seederClass())) {
                Artisan::call('db:seed', ['--class' => $manifest->seederClass(), '--force' => true]);
                $op->step('seeder');
            }

            $module = CoreModule::updateOrCreate(['slug' => $manifest->slug()], [
                ...DatabaseActivator::attributesFromManifest($manifest),
                'status' => CoreModule::STATUS_INSTALLED,
                'installed_at' => now(),
                'activated_at' => null,
                'installed_by' => Auth::id(),
            ]);
            $module->versions()->create(['from_version' => null, 'to_version' => $manifest->version(), 'performed_by' => Auth::id(), 'created_at' => now()]);
        } catch (Throwable $e) {
            return $this->undoInstall($manifest, $op, [$e->getMessage()], $removeFolderOnFailure, migrated: true);
        }

        $this->modules->refresh();
        $op->step('status: installed (inactive)')->finish();
        ModuleInstalled::dispatch($manifest);

        return $module;
    }

    /** @param list<string> $errors */
    protected function undoInstall(Manifest $manifest, ModuleOperation $op, array $errors, bool $removeFolder, bool $migrated): never
    {
        if ($migrated && is_dir($manifest->migrationsPath())) {
            Artisan::call('migrate:reset', ['--path' => $manifest->migrationsPath(), '--realpath' => true, '--force' => true]);
            $op->step('migrations rolled back');
            $this->permissions->removeAll($manifest);
            CoreModule::query()->where('slug', $manifest->slug())->where('status', '!=', CoreModule::STATUS_UNINSTALLED)->delete();
        }

        if ($removeFolder) {
            File::deleteDirectory($manifest->path);
            $op->step('module folder removed');
        }

        $this->modules->refresh();
        $this->fail($op, 'install', $errors, $migrated ? 'rolled_back' : 'failed');
    }

    // ── Activate / deactivate ────────────────────────────────────────────────

    public function activate(string $slug): void
    {
        $manifest = $this->manifestOrFail($slug, 'activate');
        $op = ModuleOperation::start($slug, 'activate');

        $errors = [...$this->inactiveDependencies($manifest), ...$this->extensions->mismatches($manifest)];
        if ($errors) {
            $this->fail($op, 'activate', $errors);
        }
        $op->step('dependencies and provides checked');

        $this->permissions->sync($manifest->name());
        $op->step('Shield permissions');

        Module::find($manifest->name())->enable();
        $this->modules->refresh();
        $this->extensions->reload();

        $registered = app(Registry::class)->forModule($slug);
        $this->permissions->syncAttachable($slug, array_column($registered['attachable'] ?? [], 'model'));
        app(RoleCatalog::class)->sync($slug);
        $op->step('main-site permissions and role catalog');

        $op->step('status: active')->finish();
        ModuleActivated::dispatch($manifest);
    }

    public function deactivate(string $slug): void
    {
        $manifest = $this->manifestOrFail($slug, 'deactivate');
        $op = ModuleOperation::start($slug, 'deactivate');

        if ($manifest->isCore()) {
            $this->fail($op, 'deactivate', ['Core modules cannot be deactivated.']);
        }

        if ($dependents = $this->activeDependents($manifest)) {
            $this->fail($op, 'deactivate', ['These active modules depend on it: '.implode(', ', $dependents).'. Deactivate them first.']);
        }

        Module::find($manifest->name())->disable();   // data, permissions, settings stay (FR-MODM-09)
        $this->modules->refresh();
        $this->extensions->reload();

        $op->step('status: inactive')->finish();
        ModuleDeactivated::dispatch($manifest);
    }

    // ── Uninstall ────────────────────────────────────────────────────────────

    /** FR-MODM-11: only inactive modules; "keep data" or "delete data". */
    public function uninstall(string $slug, bool $deleteData = false): void
    {
        $manifest = $this->manifestOrFail($slug, 'uninstall');
        $op = ModuleOperation::start($slug, 'uninstall');

        if ($this->modules->isActive($slug)) {
            $this->fail($op, 'uninstall', ['Deactivate the module before uninstalling it.']);
        }

        if ($manifest->isCore()) {
            $this->fail($op, 'uninstall', ['Core modules cannot be uninstalled.']);
        }

        if ($dependents = $this->installedDependents($manifest)) {
            $this->fail($op, 'uninstall', ['These modules depend on it: '.implode(', ', $dependents).'.']);
        }

        ModuleUninstalling::dispatch($manifest, $deleteData);
        $op->step('kernel references cleaned');

        if ($deleteData) {
            if (is_dir($manifest->migrationsPath())) {
                Artisan::call('migrate:reset', ['--path' => $manifest->migrationsPath(), '--realpath' => true, '--force' => true]);
                $op->step('tables removed');
            }
            $this->permissions->removeAll($manifest);
            $op->step('permissions removed');
        }

        if (DatabaseActivator::isBundledPath($manifest->path)) {
            // Bundled code stays in the repository; remember it is uninstalled
            CoreModule::updateOrCreate(['slug' => $slug], [
                ...DatabaseActivator::attributesFromManifest($manifest),
                'status' => CoreModule::STATUS_UNINSTALLED,
                'activated_at' => null,
            ]);
        } else {
            $backup = rtrim(config('platform.modules.backup_path'), '/')."/{$manifest->name()}-{$manifest->version()}-".now()->format('YmdHis');
            File::ensureDirectoryExists(dirname($backup));
            File::copyDirectory($manifest->path, $backup);
            File::deleteDirectory($manifest->path);
            CoreModule::query()->where('slug', $slug)->delete();
            $op->step('module folder removed', message: "backup: {$backup}");
        }

        $this->modules->refresh();
        $this->extensions->reload();
        $op->finish();
        ModuleUninstalled::dispatch($manifest);
    }

    // ── Bundled modules ─────────────────────────────────────────────────────

    /**
     * Record bundled modules that have no row yet, with their default status.
     *
     * @return list<string> slugs added
     */
    public function syncBundled(): array
    {
        $added = [];

        foreach ($this->modules->manifests() as $manifest) {
            if (! DatabaseActivator::isBundledPath($manifest->path) || CoreModule::query()->where('slug', $manifest->slug())->exists()) {
                continue;
            }

            CoreModule::create([
                ...DatabaseActivator::attributesFromManifest($manifest),
                'status' => $manifest->defaultActive() ? CoreModule::STATUS_ACTIVE : CoreModule::STATUS_INSTALLED,
                'installed_at' => now(),
                'activated_at' => $manifest->defaultActive() ? now() : null,
            ]);
            $added[] = $manifest->slug();

            // Bundled modules are migrated with the app; their default data comes now (ADR-06)
            if (class_exists($manifest->seederClass())) {
                Artisan::call('db:seed', ['--class' => $manifest->seederClass(), '--force' => true]);
            }
        }

        $this->modules->refresh();

        return $added;
    }

    // ── Dependency helpers ──────────────────────────────────────────────────

    /** @return list<string> */
    protected function missingDependencies(Manifest $manifest): array
    {
        $errors = [];

        foreach ($manifest->dependencies() as $slug => $constraint) {
            $dependency = $this->modules->manifest($slug);

            if (! $dependency) {
                $errors[] = "Needs module \"{$slug}\" ({$constraint}), which is not installed.";
            } elseif (! Semver::satisfies($dependency->version(), $constraint)) {
                $errors[] = "Needs \"{$slug}\" {$constraint}, installed version is {$dependency->version()}.";
            }
        }

        return $errors;
    }

    /** @return list<string> */
    protected function inactiveDependencies(Manifest $manifest): array
    {
        $errors = $this->missingDependencies($manifest);

        foreach (array_keys($manifest->dependencies()) as $slug) {
            if ($this->modules->manifest($slug) && ! $this->modules->isActive($slug)) {
                $errors[] = "Activate \"{$slug}\" first.";
            }
        }

        return $errors;
    }

    /** @return list<string> */
    protected function activeDependents(Manifest $manifest): array
    {
        return array_values(array_filter(
            $this->installedDependents($manifest),
            fn (string $slug): bool => $this->modules->isActive($slug),
        ));
    }

    /** @return list<string> */
    protected function installedDependents(Manifest $manifest): array
    {
        return collect($this->modules->manifests())
            ->filter(fn (Manifest $other): bool => array_key_exists($manifest->slug(), $other->dependencies()))
            ->map(fn (Manifest $other): string => $other->slug())
            ->values()
            ->all();
    }

    protected function manifestOrFail(string $slug, string $operation): Manifest
    {
        return $this->modules->manifest($slug)
            ?? throw new ModuleOperationFailed($operation, ["Module \"{$slug}\" not found."]);
    }

    /** @param list<string> $errors */
    protected function fail(ModuleOperation $op, string $operation, array $errors, string $status = 'failed'): never
    {
        $op->step($operation, 'failed', implode(' | ', $errors))->finish($status, implode("\n", $errors));

        throw new ModuleOperationFailed($operation, $errors);
    }
}
