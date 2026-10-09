<?php

/*
| Phase 2.8b — Theme Management screens (C-06): Themes (install / update by ZIP,
| activate, preview, uninstall), Design (colours, fonts, branding, options, reset,
| copy to admin), Layouts. FR-THEME-01..12, BRL-THEME-01, AC-THEME-01/02.
*/

use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\ThemeManager\Filament\Pages\ManageDesign;
use Modules\ThemeManager\Filament\Pages\ManageLayouts;
use Modules\ThemeManager\Filament\Pages\ManageThemes;
use Platform\Media\MediaLibrary;
use Platform\Theme\DesignTokens;
use Platform\Theme\LayoutResolver;
use Platform\Theme\Theme;
use Platform\Theme\Themes;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    foreach (['View:Theme', 'Install:Theme', 'Activate:Theme', 'Customize:Theme'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    Storage::fake('public');
    $this->runtime = storage_path('framework/testing/themes-'.uniqid());
    $this->work = storage_path('framework/testing/theme-src-'.uniqid());
    config(['platform.themes.runtime_path' => $this->runtime]);
    $this->themes = app(Themes::class);
    $this->themes->flush();
    $this->themes->sync();
});

afterEach(function () {
    File::deleteDirectory($this->runtime);
    File::deleteDirectory($this->work);
    File::deleteDirectory(public_path('themes/harbor'));
    app(Themes::class)->flush();
    app(Themes::class)->usePreview(null);
});

/** A ZIP of a copy of the default theme as "harbor" (version, extra files). */
function harborZip(string $work, string $version = '1.0.0', array $files = []): string
{
    $dir = "{$work}/{$version}/harbor";
    File::deleteDirectory(dirname($dir));
    File::copyDirectory(base_path('themes/default'), $dir);

    $json = json_decode(File::get("{$dir}/theme.json"), true);
    File::put("{$dir}/theme.json", json_encode([...$json, 'slug' => 'harbor', 'name' => 'Harbor', 'version' => $version]));
    File::put("{$dir}/views/home.blade.php", '<x-theme::layout><p>HARBOR-HOME</p></x-theme::layout>');

    foreach ($files as $path => $content) {
        File::ensureDirectoryExists(dirname("{$dir}/{$path}"));
        File::put("{$dir}/{$path}", $content);
    }

    $zipPath = "{$work}/harbor-{$version}.zip";
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach (File::allFiles($dir) as $file) {
        $zip->addFile($file->getPathname(), 'harbor/'.str_replace('\\', '/', $file->getRelativePathname()));
    }
    $zip->close();

    return $zipPath;
}

it('lets only theme users in, each screen with its permission (SRS C-06 permissions)', function () {
    $this->actingAs(makeUser());
    $this->get(ManageThemes::getUrl())->assertForbidden();
    $this->get(ManageDesign::getUrl())->assertForbidden();

    $this->actingAs(makeUser(['View:Theme']));
    $this->get(ManageThemes::getUrl())->assertOk()->assertSee('University Default')->assertSee(route('theme-manager.screenshot', 'default'));
    $this->get(route('theme-manager.screenshot', 'default'))->assertOk();
    $this->get(ManageDesign::getUrl())->assertForbidden();
    $this->get(ManageLayouts::getUrl())->assertForbidden();

    $this->actingAs(makeUser(['Customize:Theme']));
    $this->get(ManageDesign::getUrl())->assertOk();
    $this->get(ManageLayouts::getUrl())->assertOk()->assertSee('Faq — Listing page');
});

it('installs a theme ZIP, updates it, refuses older or unsafe ones (FR-THEME-02)', function () {
    $this->actingAs(superAdmin());

    $theme = $this->themes->install(harborZip($this->work));
    expect($theme->slug)->toBe('harbor')
        ->and($theme->is_bundled)->toBeFalse()
        ->and($theme->installed_by)->toBe(auth()->id())
        ->and(File::exists("{$this->runtime}/harbor/theme.json"))->toBeTrue();

    expect($this->themes->install(harborZip($this->work, '1.1.0'))->version)->toBe('1.1.0')
        ->and(Theme::query()->where('slug', 'harbor')->count())->toBe(1);

    expect(fn () => $this->themes->install(harborZip($this->work, '1.0.5')))->toThrow(RuntimeException::class, 'older');
    expect(fn () => $this->themes->install(harborZip($this->work, '2.0.0', ['helpers.php' => '<?php echo 1;'])))
        ->toThrow(RuntimeException::class, 'PHP file not allowed');
    expect($this->themes->find('harbor')->version)->toBe('1.1.0');                            // the failed update changed nothing

    // A bundled theme is never replaced by a ZIP
    $default = harborZip($this->work, '9.0.0');
    $zip = new ZipArchive;
    $zip->open($default);
    $zip->addFromString('harbor/theme.json', json_encode([...json_decode(File::get(base_path('themes/default/theme.json')), true), 'version' => '9.0.0']));
    $zip->close();
    expect(fn () => $this->themes->install($default))->toThrow(RuntimeException::class, 'bundled');
});

it('uploads a theme from the Themes screen', function () {
    $this->actingAs(superAdmin());
    $upload = UploadedFile::fake()->createWithContent('harbor.zip', File::get(harborZip($this->work)));

    Livewire::test(ManageThemes::class)
        ->callAction('install', data: ['package' => $upload])
        ->assertNotified();

    expect($this->themes->find('harbor'))->not->toBeNull();
});

it('activates, previews and uninstalls from the Themes screen (FR-THEME-01/03, BRL-THEME-01)', function () {
    $this->actingAs(superAdmin());
    $this->themes->install(harborZip($this->work));

    Livewire::test(ManageThemes::class)
        ->callAction('preview', arguments: ['slug' => 'harbor'])
        ->assertRedirect(url('/'));
    expect(session(Themes::PREVIEW_SESSION))->toBe('harbor');
    $this->get('/')->assertSee('HARBOR-HOME')->assertSee('Stop preview');

    Livewire::test(ManageThemes::class)->callAction('activate', arguments: ['slug' => 'harbor'])->assertNotified();
    expect($this->themes->active()->slug)->toBe('harbor')
        ->and(session(Themes::PREVIEW_SESSION))->toBeNull();

    // The active theme and a bundled theme stay
    Livewire::test(ManageThemes::class)->callAction('uninstall', arguments: ['slug' => 'harbor']);
    Livewire::test(ManageThemes::class)->callAction('uninstall', arguments: ['slug' => 'default']);
    expect(Theme::query()->pluck('slug')->sort()->values()->all())->toBe(['default', 'harbor']);

    Livewire::test(ManageThemes::class)->callAction('activate', arguments: ['slug' => 'default']);
    Livewire::test(ManageThemes::class)->callAction('uninstall', arguments: ['slug' => 'harbor'])->assertNotified();
    expect($this->themes->find('harbor'))->toBeNull()
        ->and(File::exists("{$this->runtime}/harbor"))->toBeFalse();
});

it('hides theme actions from users without the permission', function () {
    $this->actingAs(makeUser(['View:Theme']));
    $this->themes->install(harborZip($this->work));

    Livewire::test(ManageThemes::class)
        ->assertActionHidden('install')
        ->assertActionHidden('activate')
        ->assertActionHidden('uninstall');
});

it('saves colours, fonts and options; warns on low contrast; resets (FR-THEME-04/05/12, AC-THEME-01)', function () {
    $this->actingAs(makeUser(['Customize:Theme']));
    $theme = $this->themes->active();

    Livewire::test(ManageDesign::class)
        ->assertSchemaStateSet(['tokens.color-primary' => '#034EA2', 'options.sticky_header' => true])
        ->fillForm(['tokens.color-text-muted' => '#DDDDDD'])
        ->assertSee('needs 4.5:1')
        ->fillForm(['tokens.color-text-muted' => '#5B6470', 'tokens.color-primary' => '#7A1F5C', 'tokens.font-heading' => 'Poppins', 'options.hero_height' => 'medium'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect(app(DesignTokens::class)->overrides($theme))->toEqualCanonicalizing(['color-primary' => '#7A1F5C', 'font-heading' => 'Poppins'])
        ->and(theme_option('hero_height'))->toBe('medium');

    $this->get('/')->assertSee('family=Poppins', false);                                       // the CSS is written on first use
    $css = Storage::disk('public')->get(Storage::disk('public')->files('theme')[0]);
    expect($css)->toContain('--color-primary: #7A1F5C;')->toContain('"Poppins"');

    Livewire::test(ManageDesign::class)->call('resetDesign')
        ->assertSchemaStateSet(['tokens.color-primary' => '#034EA2', 'tokens.font-heading' => 'Montserrat']);
    expect(app(DesignTokens::class)->overrides($theme))->toBe([])
        ->and(theme_option('hero_height'))->toBe('tall');
});

it('keeps website branding and copies it to the admin panel (FR-THEME-06, FR-SET-13)', function () {
    $this->actingAs(superAdmin());
    $logo = app(MediaLibrary::class)->store(UploadedFile::fake()->image('logo.png', 400, 120));

    Livewire::test(ManageDesign::class)
        ->fillForm(['branding.logo_light' => $logo->id])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(settings()->media('branding.logo_light')?->is($logo))->toBeTrue()
        ->and($logo->refresh()->usage_count)->toBe(1);
    $this->get('/')->assertSee($logo->url(), false);

    // Branding stays when the design is reset
    Livewire::test(ManageDesign::class)->call('resetDesign');
    expect(settings('branding.logo_light'))->toBe($logo->id);

    Livewire::test(ManageDesign::class)->call('copyToAdmin')->assertNotified();
    expect(settings('admin_branding.logo_light'))->toBe($logo->id)
        ->and(settings('admin_branding.primary_color'))->toBe('#034EA2');
});

it('chooses a layout and options per module page (FR-THEME-08/09, AC-THEME-02)', function () {
    $this->actingAs(makeUser(['Customize:Theme']));
    $field = ManageLayouts::field('faq.listing');

    Livewire::test(ManageLayouts::class)
        ->assertSchemaStateSet(["{$field}.layout" => 'accordion'])
        ->fillForm(["{$field}.layout" => 'grouped', "{$field}.per_row" => 2, "{$field}.sidebar" => true])
        ->call('save')
        ->assertNotified();

    $layouts = app(LayoutResolver::class);
    expect($layouts->for('faq.listing'))->toBe('grouped')
        ->and($layouts->options('faq.listing'))->toBe(['per_row' => 2, 'sidebar' => true]);
});
