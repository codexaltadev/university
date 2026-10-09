<?php

/*
| Phase 1E — architecture rules (SAD Part A §2 D-1..D-6, Module Guide §2 R-1..R-5).
| They keep third-party modules from reaching into each other or into the kernel's tables.
*/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Platform\Models\BaseModel;
use Platform\Models\BasePivot;
use Platform\Models\ConfigModel;
use Platform\Modules\Manifest;
use Platform\Modules\ManifestValidator;
use Platform\Policies\ScopedPolicy;
use Symfony\Component\Finder\Finder;

/** @return array<string, array{Manifest}> bundled modules (datasets run before the app boots: no base_path()) */
function bundledManifests(): array
{
    $manifests = [];
    foreach (glob(dirname(__DIR__, 2).'/Modules/*/module.json') ?: [] as $file) {
        $manifest = Manifest::fromDirectory(dirname($file));
        $manifests[$manifest->name()] = [$manifest];
    }

    return $manifests;
}

/** @return list<class-string> classes in a module folder */
function classesIn(string $directory, string $namespace): array
{
    if (! is_dir($directory)) {
        return [];
    }

    $classes = [];
    foreach (Finder::create()->files()->in($directory)->name('*.php') as $file) {
        $class = $namespace.'\\'.Str::of($file->getRelativePathname())->beforeLast('.php')->replace('/', '\\');
        if (class_exists($class)) {
            $classes[] = $class;
        }
    }

    return $classes;
}

// ─── D-1: the kernel never depends on modules ──────────────────────────────

arch('the kernel does not use module code')
    ->expect('Platform')
    ->not->toUse('Modules');

arch('no debugging leftovers')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r', 'die'])
    ->not->toBeUsed();

// ─── R-3: modules use only their hard dependencies ─────────────────────────

it('modules use other modules only when declared as dependencies (R-3)', function (Manifest $manifest) {
    $allowed = array_keys($manifest->dependencies());

    foreach (bundledManifests() as [$other]) {
        if ($other->name() === $manifest->name() || in_array($other->slug(), $allowed, true)) {
            continue;
        }

        expect($manifest->namespace())->not->toUse($other->namespace());
    }
})->with(fn () => bundledManifests());

// ─── R-1 / R-2: own tables only ────────────────────────────────────────────

it('modules query only their own tables, never another module\'s or the kernel\'s directly (R-1, R-2)', function (Manifest $manifest) {
    $violations = [];

    foreach (Finder::create()->files()->in($manifest->path)->name('*.php')->notPath('tests') as $file) {
        preg_match_all("/(?:DB::table|->from|->join|Schema::(?:create|table|drop|dropIfExists))\\(\\s*'([A-Za-z0-9_]+)/", $file->getContents(), $matches);

        foreach ($matches[1] as $table) {
            if (! str_starts_with($table, $manifest->tablePrefix())) {
                $violations[] = "{$file->getRelativePathname()}: {$table}";
            }
        }
    }

    expect($violations)->toBe([]);
})->with(fn () => bundledManifests());

// ─── R-4: models extend the platform bases ─────────────────────────────────

it('module models extend BaseModel, BasePivot or ConfigModel (R-4)', function (Manifest $manifest) {
    $wrong = array_filter(
        classesIn($manifest->path.'/app/Models', $manifest->namespace().'\\Models'),
        fn (string $class): bool => is_subclass_of($class, Model::class)
            && ! is_subclass_of($class, BaseModel::class) && ! is_subclass_of($class, BasePivot::class)
            && ! is_subclass_of($class, ConfigModel::class),   // configuration (C) tables, DB-01 §1.1
    );

    expect($wrong)->toBe([]);
})->with(fn () => bundledManifests());

it('module policies extend ScopedPolicy (SAD Part A §7.4)', function (Manifest $manifest) {
    $wrong = array_filter(
        classesIn($manifest->path.'/app/Policies', $manifest->namespace().'\\Policies'),
        fn (string $class): bool => ! is_subclass_of($class, ScopedPolicy::class),
    );

    expect($wrong)->toBe([]);
})->with(fn () => bundledManifests());

// ─── R-5: no composer dependencies in modules ──────────────────────────────

it('modules bring no composer packages (R-5, ADR-03)', function (Manifest $manifest) {
    $composer = json_decode((string) file_get_contents($manifest->path.'/composer.json'), true);

    expect($composer['require'] ?? [])->toBe([]);
})->with(fn () => bundledManifests());

// ─── Manifest ↔ code ───────────────────────────────────────────────────────

it('every bundled module has an Extension and a valid manifest', function (Manifest $manifest) {
    expect(class_exists($manifest->extensionClass()))->toBeTrue()
        ->and(app(ManifestValidator::class)->validate($manifest))->toBe([]);
})->with(fn () => bundledManifests());
