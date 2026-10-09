<?php

namespace Platform\Modules;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Symfony\Component\Finder\Finder;
use Throwable;

/**
 * Install-time conflict checks (SAD Part A §10.1 step 5, FR-MODM-03 e).
 */
class ConflictChecker
{
    public function __construct(
        protected ModuleRegistry $modules,
        protected ModulePermissions $permissions,
    ) {}

    /** @return list<string> */
    public function check(Manifest $manifest): array
    {
        $others = array_filter(
            $this->modules->manifests(),
            fn (Manifest $other): bool => realpath($other->path) !== realpath($manifest->path),
        );

        $errors = [];

        foreach ($others as $other) {
            if ($other->slug() === $manifest->slug()) {
                $errors[] = "Slug \"{$manifest->slug()}\" is already used by module {$other->name()}.";
            }
            if ($other->name() === $manifest->name()) {
                $errors[] = "A module named {$manifest->name()} already exists.";
            }
            foreach (array_intersect_key($manifest->morphAliases(), $other->morphAliases()) as $alias => $class) {
                $errors[] = "Morph alias \"{$alias}\" is already used by module {$other->name()}.";
            }
        }

        // Shield keys come from model names; resources must be unique too (SAD §7.4)
        $taken = $this->classBasenames($others);
        foreach ($this->classBasenamesOf($manifest) as $basename) {
            if (isset($taken[$basename])) {
                $errors[] = "Class name {$basename} is already used by {$taken[$basename]}; use a module prefix.";
            }
        }

        foreach ($this->permissions->keysForDirectory($manifest->path, $manifest->name()) as $key) {
            $owner = Permission::query()->where('name', $key)->value('module_slug');
            if ($owner !== null && $owner !== $manifest->slug()) {
                $errors[] = "Permission {$key} already belongs to module \"{$owner}\".";
            }
        }

        return [...$errors, ...$this->checkTables($manifest)];
    }

    /**
     * Tables created by pending migrations must use the prefix and not exist yet.
     *
     * @return list<string>
     */
    public function checkTables(Manifest $manifest): array
    {
        if (! is_dir($manifest->migrationsPath())) {
            return [];
        }

        try {
            Artisan::call('migrate', ['--path' => $manifest->migrationsPath(), '--realpath' => true, '--pretend' => true, '--force' => true]);
        } catch (Throwable $e) {
            return ['A migration fails in a dry run: '.$e->getMessage()];
        }

        preg_match_all('/create table [`"]?([A-Za-z0-9_]+)[`"]?/i', Artisan::output(), $matches);

        $errors = [];

        foreach (array_unique($matches[1]) as $table) {
            if (! str_starts_with($table, $manifest->tablePrefix())) {
                $errors[] = "Table {$table} must start with \"{$manifest->tablePrefix()}\".";
            } elseif (Schema::hasTable($table)) {
                $errors[] = "Table {$table} already exists.";
            }
        }

        return $errors;
    }

    /**
     * @param  array<Manifest>  $manifests
     * @return array<string, string> basename => module name
     */
    protected function classBasenames(array $manifests): array
    {
        $names = ['User' => 'core', 'Role' => 'core', 'Permission' => 'core', 'RoleResource' => 'core'];

        foreach ($manifests as $manifest) {
            foreach ($this->classBasenamesOf($manifest) as $basename) {
                $names[$basename] = $manifest->name();
            }
        }

        return $names;
    }

    /** @return list<string> model and Filament resource / page / widget class names */
    protected function classBasenamesOf(Manifest $manifest): array
    {
        $names = [];

        foreach (['app/Models' => '*.php', 'app/Filament' => '*{Resource,Page,Widget}.php'] as $dir => $pattern) {
            if (! is_dir("{$manifest->path}/{$dir}")) {
                continue;
            }
            $finder = Finder::create()->files()->in("{$manifest->path}/{$dir}");
            $finder = $dir === 'app/Models' ? $finder->depth(0)->name('*.php') : $finder->name('/(Resource|Page|Widget)\.php$/');

            foreach ($finder as $file) {
                $names[] = Str::beforeLast($file->getFilename(), '.php');
            }
        }

        return array_values(array_unique($names));
    }
}
