<?php

/*
| Phase 2.8a — Theme engine (C-06, SAD Part B §8): installed themes, checks, view
| override order, design tokens → CSS, layout selection, preview, Blade helpers.
| FR-THEME-02/03/04/07/08/10/11/12, BRL-THEME-01/02, AC-THEME-01/02.
*/

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Platform\Registry\Registry;
use Platform\Theme\Contrast;
use Platform\Theme\DesignTokens;
use Platform\Theme\LayoutResolver;
use Platform\Theme\Theme;
use Platform\Theme\ThemeChecker;
use Platform\Theme\Themes;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Storage::fake('public');
    $this->runtime = storage_path('framework/testing/themes-'.uniqid());
    config(['platform.themes.runtime_path' => $this->runtime]);
    $this->themes = app(Themes::class);
    $this->themes->flush();
});

afterEach(function () {
    File::deleteDirectory($this->runtime);
    File::deleteDirectory(public_path('themes/campus'));
    app(Themes::class)->flush();
    app(Themes::class)->usePreview(null);
});

/** A copy of the default theme as "campus", with its own home page and changes. */
function campusTheme(string $runtime, array $manifest = [], array $files = []): string
{
    $dir = "{$runtime}/campus";
    File::copyDirectory(base_path('themes/default'), $dir);

    $json = json_decode(File::get("{$dir}/theme.json"), true);
    File::put("{$dir}/theme.json", json_encode(array_replace_recursive($json, ['slug' => 'campus', 'name' => 'Campus'], $manifest)));
    File::put("{$dir}/views/home.blade.php", '<x-theme::layout><p>CAMPUS-HOME</p></x-theme::layout>');

    foreach ($files as $path => $content) {
        File::ensureDirectoryExists(dirname("{$dir}/{$path}"));
        File::put("{$dir}/{$path}", $content);
    }

    app(Themes::class)->sync();

    return $dir;
}

it('records the bundled default theme as active and keeps it the fallback (BRL-THEME-01)', function () {
    $this->themes->sync();

    $default = Theme::query()->where('slug', 'default')->first();
    expect($default->is_active)->toBeTrue()
        ->and($default->is_bundled)->toBeTrue()
        ->and($default->source_path)->toBe('themes/default')
        ->and($this->themes->active()->slug)->toBe('default')
        ->and(app(ThemeChecker::class)->check(base_path('themes/default')))->toBe([]);

    $this->artisan('uwms:theme:check', ['slug' => 'default'])->assertSuccessful();
});

it('rejects a theme with PHP logic or missing parts (FR-THEME-02, BRL-THEME-02)', function () {
    $dir = campusTheme($this->runtime, files: [
        'views/partials/bad.blade.php' => '@php $x = 1; @endphp {{ App\\Models\\User::query()->count() }}',
        'helpers.php' => '<?php function evil() {}',
        'views/partials/icon.blade.php' => '<x-theme::icon name="x" x-bind:class="open === @js($id)" />',   // Blade leaves it as text: a JavaScript error (4.8a)
    ]);
    File::delete("{$dir}/views/templates/landing.blade.php");
    File::delete("{$dir}/public/build/manifest.json");

    $errors = implode("\n", app(ThemeChecker::class)->check($dir));

    expect($errors)->toContain('@php block')
        ->toContain('model call')
        ->toContain('@js / @json in a component tag')
        ->toContain('PHP file not allowed in a theme: helpers.php')
        ->toContain('views/templates/landing.blade.php')
        ->toContain('public/build/manifest.json');

    expect(fn () => $this->themes->activate('campus'))->toThrow(RuntimeException::class);
    expect($this->themes->active()->slug)->toBe('default');
});

it('activates another theme, logs it and publishes its assets', function () {
    campusTheme($this->runtime);
    $this->actingAs(superAdmin());

    $this->artisan('uwms:theme:activate', ['slug' => 'campus'])->assertSuccessful();

    expect($this->themes->active()->slug)->toBe('campus')
        ->and(Theme::query()->where('is_active', true)->count())->toBe(1)
        ->and(File::exists(public_path('themes/campus/build/manifest.json')))->toBeTrue()
        ->and(Activity::query()->where('log_name', 'theme')->where('event', 'activated')->exists())->toBeTrue();

    $this->get('/')->assertOk()->assertSee('CAMPUS-HOME');
});

it('draws module views from the active theme first, then the module (FR-THEME-10, FR-THEME-11)', function () {
    campusTheme($this->runtime, files: ['views/modules/faq/blocks/faq.blade.php' => 'THEME-OVERRIDE']);

    expect(view('faq::blocks.faq')->getPath())->toStartWith(base_path('Modules/Faq'));

    $this->themes->activate('campus');
    $this->themes->apply();
    expect(view('faq::blocks.faq')->getPath())->toStartWith($this->runtime.'/campus/views/modules/faq');

    // Back to a theme without the override: the module's own view, content untouched
    $this->themes->activate('default');
    $this->themes->apply();
    expect(view('faq::blocks.faq')->getPath())->toStartWith(base_path('Modules/Faq'));
});

it('turns design tokens into a hashed CSS file with the admin\'s colours (FR-THEME-04/07, AC-THEME-01)', function () {
    $tokens = app(DesignTokens::class);
    $theme = $this->themes->active();

    $first = $tokens->stylesheetUrl($theme);
    expect($tokens->css($theme))->toContain('--color-primary: #034EA2;')
        ->toContain('--font-heading: "Manrope"')   // default theme = DIU copy: Manrope for headings too
        ->toContain(':lang(bn)');

    $tokens->save($theme, ['color-primary' => '#7A1F5C', 'color-secondary' => '#002652']);

    $second = $tokens->stylesheetUrl($theme);
    $file = 'theme/'.basename(parse_url($second, PHP_URL_PATH));
    expect($second)->not->toBe($first)
        ->and(Storage::disk('public')->get($file))->toContain('--color-primary: #7A1F5C;')
        ->and(Storage::disk('public')->files('theme'))->toHaveCount(1)              // the old file is removed
        ->and($tokens->overrides($theme))->toBe(['color-primary' => '#7A1F5C']);   // same as default → not stored

    $this->get('/')->assertSee(basename($file));

    // FR-THEME-12: back to the theme's own values
    $tokens->reset($theme);
    expect($tokens->overrides($theme))->toBe([])
        ->and($tokens->values($theme)['color-primary'])->toBe('#034EA2');
});

it('warns about weak contrast and picks readable text on colours (FR-THEME-04)', function () {
    expect(Contrast::ratio('#000000', '#FFFFFF'))->toBe(21.0)
        ->and(Contrast::readableOn('#034EA2'))->toBe('#FFFFFF')
        ->and(Contrast::readableOn('#F5D90A'))->toBe('#111827');

    $warnings = DesignTokens::contrastWarnings(['color-bg' => '#FFFFFF', 'color-primary' => '#F5A623', 'color-text' => '#1F2328']);
    expect($warnings)->toHaveKey('color-primary')->not->toHaveKey('color-text');
});

it('selects a module layout and falls back when the theme cannot draw it (FR-THEME-08/09/11, AC-THEME-02)', function () {
    app(Registry::class)->add('layouts', 'faq', ['page' => 'faq.archive', 'layouts' => ['accordion', 'list']]);
    $layouts = app(LayoutResolver::class);

    expect($layouts->for('faq.archive'))->toBe('accordion');

    $layouts->choose('faq.archive', 'list', ['per_row' => 2]);
    expect($layouts->for('faq.archive'))->toBe('list')
        ->and($layouts->options('faq.archive'))->toBe(['per_row' => 2]);

    // A layout only the campus theme adds
    campusTheme($this->runtime, ['layouts' => ['faq.archive' => ['magazine'], 'no-such-module.listing' => ['grid']]]);   // a page of a module that is not installed
    $layouts->choose('faq.archive', 'magazine');
    expect($layouts->for('faq.archive'))->toBe('accordion');                    // default theme cannot draw it

    $this->themes->activate('campus');
    expect($layouts->available('faq.archive'))->toBe(['accordion', 'list', 'magazine'])
        ->and($layouts->for('faq.archive'))->toBe('magazine')
        ->and($layouts->pages())->not->toHaveKey('no-such-module.listing');     // module not active (T-4)
});

it('previews a theme only for the admin who asked (FR-THEME-03)', function () {
    campusTheme($this->runtime);
    Permission::findOrCreate(Themes::PREVIEW_PERMISSION, 'web');
    $admin = makeUser([Themes::PREVIEW_PERMISSION]);

    $this->actingAs($admin)->withSession([Themes::PREVIEW_SESSION => 'campus'])
        ->get('/')->assertOk()->assertSee('CAMPUS-HOME')->assertSee('noindex', false)->assertSee('Stop preview');

    // Someone without the permission (or a visitor) with the same session sees the active theme
    $this->actingAs(makeUser())->withSession([Themes::PREVIEW_SESSION => 'campus'])
        ->get('/')->assertOk()->assertDontSee('CAMPUS-HOME');

    $this->actingAs($admin)->withSession([Themes::PREVIEW_SESSION => 'campus'])
        ->get('/theme-preview/stop?back=//evil.example')->assertRedirect('/')->assertSessionMissing(Themes::PREVIEW_SESSION);
});

it('gives themes Blade helpers: options, module checks, assets (Theme Guide §5, §11)', function () {
    expect(theme_option('sticky_header'))->toBeTrue()
        ->and(theme_option('hero_height'))->toBe('tall');

    $this->themes->saveOptions($this->themes->active(), ['hero_height' => 'medium', 'unknown' => 1]);
    expect(theme_option('hero_height'))->toBe('medium')
        ->and(settings()->group('theme.default')['options'])->toBe(['hero_height' => 'medium']);

    expect(Blade::render("@moduleActive('faq')on @endmoduleActive|@moduleActive('nothing')on @endmoduleActive"))->toBe('on |')
        ->and((string) $this->themes->vite(['resources/css/app.css']))->toMatch('#/themes/default/build/assets/app-[\w-]+\.css#');
});
