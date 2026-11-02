<?php

/*
| uwms:theme:make (Theme Guide §3): a blank theme the checker accepts once built, a copy of an
| installed theme under a new slug, and the refusals (bad slug, existing theme, unknown source).
*/

use Illuminate\Support\Facades\File;
use Platform\Theme\ThemeChecker;
use Platform\Theme\ThemeManifest;

beforeEach(function () {
    $this->slug = 'maketest'.substr(md5(uniqid()), 0, 6);
    $this->dir = base_path("themes/{$this->slug}");
});

afterEach(function () {
    File::deleteDirectory($this->dir);
});

it('creates a blank theme that passes the checks once its assets are built', function () {
    $this->artisan('uwms:theme:make', ['slug' => $this->slug, '--name' => 'Make Test'])->assertSuccessful();

    $manifest = ThemeManifest::fromDirectory($this->dir);

    expect($manifest->slug())->toBe($this->slug)
        ->and($manifest->name())->toBe('Make Test')
        ->and($manifest->version())->toBe('1.0.0')
        ->and($manifest->variants('header'))->not->toBeEmpty()
        ->and(File::exists("{$this->dir}/views/errors/404.blade.php"))->toBeTrue()
        ->and(File::exists("{$this->dir}/lang/bn/theme.php"))->toBeTrue()
        ->and(File::get("{$this->dir}/resources/css/app.css"))->toContain('@import "tailwindcss"');

    // Only the build is missing (T-6); with a manifest the theme is accepted
    expect(app(ThemeChecker::class)->check($this->dir))->toBe(['Missing compiled assets: public/build/manifest.json (T-6).']);

    File::ensureDirectoryExists("{$this->dir}/public/build");
    File::put("{$this->dir}/public/build/manifest.json", '{}');

    expect(app(ThemeChecker::class)->check($this->dir))->toBe([]);
});

it('copies an installed theme under a new slug without its build output', function () {
    $this->artisan('uwms:theme:make', ['slug' => $this->slug, '--from' => 'default'])->assertSuccessful();

    $manifest = ThemeManifest::fromDirectory($this->dir);

    expect($manifest->slug())->toBe($this->slug)
        ->and($manifest->version())->toBe('1.0.0')
        ->and($manifest->sections())->toBe(ThemeManifest::fromDirectory(base_path('themes/default'))->sections())
        ->and(File::exists("{$this->dir}/views/layouts/app.blade.php"))->toBeTrue()
        ->and(File::exists("{$this->dir}/public/build"))->toBeFalse();
});

it('refuses a bad slug, an existing theme and an unknown source', function () {
    $this->artisan('uwms:theme:make', ['slug' => 'Bad Slug'])->assertFailed();
    $this->artisan('uwms:theme:make', ['slug' => 'default'])->assertFailed();
    $this->artisan('uwms:theme:make', ['slug' => $this->slug, '--from' => 'nothing-here'])->assertFailed();
    $this->artisan('uwms:theme:make', ['slug' => $this->slug, '--from' => 'default', '--blank' => true])->assertFailed();

    expect(File::exists($this->dir))->toBeFalse();
});
