<?php

/*
| Phase 2.4 — Website Settings (C-05): settings(), the Settings page and its tabs,
| permissions, module tabs, hidden modules, mail, maintenance, admin branding
| (FR-SET-01..13, BRL-SET-01/02, AC-SET-01/02).
*/

use Filament\Facades\Filament;
use Filament\Schemas\Components\Tabs\Tab;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Modules\Faq\Models\Faq;
use Modules\Settings\Filament\Pages\ManageSettings;
use Modules\Settings\Groups\AdminBrandingSettings;
use Platform\Activity\Activity;
use Platform\Events\ModuleUninstalling;
use Platform\Events\SettingsUpdated;
use Platform\Modules\Manifest;
use Platform\Modules\ModuleManager;
use Platform\Settings\Setting;
use Platform\Settings\Settings;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    foreach (['View:Settings', 'Update:Settings', 'Advanced:Settings'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
});

function tabsOf(Testable $page): array
{
    return collect($page->instance()->form->getFlatComponents())
        ->filter(fn ($c) => $c instanceof Tab)
        ->map(fn ($tab) => str($tab->getKey())->after('tab-')->toString())
        ->values()->all();
}

it('reads values in the current language, with defaults and fallback (FR-SET-11)', function () {
    expect(settings('general.date_format'))->toBe('d M Y')                     // the tab's default
        ->and(settings('general.unknown', 'x'))->toBe('x');

    settings()->save('general', ['site_name' => ['en' => 'Daffodil International University', 'bn' => 'ড্যাফোডিল ইন্টারন্যাশনাল ইউনিভার্সিটি']], translatable: ['site_name']);
    settings()->save('general', ['tagline' => ['en' => 'Excellence']], translatable: ['tagline']);

    app()->setLocale('bn');
    expect(settings('general.site_name'))->toBe('ড্যাফোডিল ইন্টারন্যাশনাল ইউনিভার্সিটি')
        ->and(settings('general.tagline'))->toBe('Excellence');                // no Bangla: default language
});

it('saves the page, clears the cache and logs each change (AC-SET-01, FR-SET-12)', function () {
    Event::fake([SettingsUpdated::class]);
    $this->actingAs(makeUser(['View:Settings', 'Update:Settings']));
    expect(settings('contact.phone'))->toBeNull();                               // now cached

    Livewire::test(ManageSettings::class)
        ->fillForm(['contact' => ['phone' => '+880 9617 901 212'], 'social' => ['links' => [['platform' => 'facebook', 'url' => 'https://facebook.com/diu']]]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(settings('contact.phone'))->toBe('+880 9617 901 212')
        ->and(settings('social.links'))->toEqual([['platform' => 'facebook', 'url' => 'https://facebook.com/diu']])
        ->and(Setting::where('group', 'contact')->where('key', 'phone')->value('updated_by'))->toBe(auth()->id());

    Event::assertDispatched(SettingsUpdated::class, fn ($e) => $e->group === 'contact' && $e->keys === ['phone']);

    $phone = Setting::where('group', 'contact')->where('key', 'phone')->first();
    $log = Activity::where('subject_type', 'core.setting')->where('subject_id', $phone->id)->first();
    expect($log->attribute_changes['attributes'])->toBe(['contact.phone' => '+880 9617 901 212'])
        ->and($log->causer_id)->toBe(auth()->id());

    Livewire::test(ManageSettings::class)->call('save');                         // nothing changed: nothing written
    Event::assertDispatchedTimes(SettingsUpdated::class, 2);                     // contact + social only
});

it('needs View:Settings to open and Update:Settings to save', function () {
    $this->actingAs(makeUser())->get(ManageSettings::getUrl())->assertForbidden();

    $viewer = makeUser(['View:Settings']);
    $this->actingAs($viewer)->get(ManageSettings::getUrl())->assertOk();
    Livewire::test(ManageSettings::class)->call('save')->assertForbidden();
});

it('shows mail and maintenance to Advanced:Settings, custom code to Super Admin only (BRL-SET-02)', function () {
    $this->actingAs(makeUser(['Update:Settings']));
    expect(tabsOf(Livewire::test(ManageSettings::class)))
        ->toContain('general', 'contact', 'social', 'media', 'modules', 'admin_branding', 'faq')
        ->not->toContain('mail', 'maintenance', 'code');

    $this->actingAs(makeUser(['Update:Settings', 'Advanced:Settings']));
    expect(tabsOf(Livewire::test(ManageSettings::class)))->toContain('mail', 'maintenance')->not->toContain('code');

    $this->actingAs(superAdmin());
    expect(tabsOf(Livewire::test(ManageSettings::class)))->toContain('mail', 'maintenance', 'code');
});

it('offers the custom permissions in the role editor', function () {
    expect(config('filament-shield.custom_permissions'))
        ->toContain('View:Settings', 'Update:Settings', 'Advanced:Settings', 'Update:InterfaceText');
});

it('shows a module\'s tab only while the module is active (FR-SET-10, AC-SET-02)', function () {
    app(ModuleManager::class)->syncBundled();
    expect(settings('faq.per_page'))->toBe(20)
        ->and(settings()->definition('faq'))->not->toBeNull();

    app(ModuleManager::class)->deactivate('faq');

    expect(settings()->definition('faq'))->toBeNull()
        ->and(collect(settings()->definitions())->map->group()->all())->not->toContain('faq');
});

it('hides an active module from the website only (FR-SET-05)', function () {
    $faq = Faq::factory()->create(['is_active' => true]);
    expect(Faq::query()->frontendVisible()->count())->toBe(1);

    settings()->save('modules', ['hidden_on_website' => ['faq']]);

    expect(Faq::query()->frontendVisible()->count())->toBe(0)
        ->and(Faq::query()->count())->toBe(1);                                   // admin still lists it
});

it('stores the SMTP password encrypted, never shows or logs it, keeps it when left empty (FR-SET-07)', function () {
    $this->actingAs(superAdmin());

    Livewire::test(ManageSettings::class)
        ->fillForm(['mail' => ['enabled' => true, 'host' => 'smtp.example.edu', 'port' => 465, 'username' => 'web', 'password' => 's3cret', 'encryption' => 'ssl']])
        ->call('save')
        ->assertHasNoFormErrors();

    $row = Setting::where('group', 'mail')->where('key', 'password')->first();
    expect($row->value)->not->toContain('s3cret')
        ->and($row->is_public)->toBeFalse()
        ->and(settings('mail.password'))->toBe('s3cret')
        ->and(config('mail.mailers.smtp.host'))->toBe('smtp.example.edu')        // applied right away
        ->and(config('mail.mailers.smtp.scheme'))->toBe('smtps')
        ->and(Activity::where('subject_type', 'core.setting')->where('subject_id', $row->id)->first()->attribute_changes['attributes']['mail.password'])->toBe('••••••');

    Livewire::test(ManageSettings::class)
        ->assertFormSet(['mail.password' => null])                               // never sent to the browser
        ->fillForm(['mail' => ['enabled' => true, 'host' => 'smtp2.example.edu', 'port' => 465, 'encryption' => 'ssl', 'password' => '']])
        ->call('save');

    expect(settings('mail.password'))->toBe('s3cret')
        ->and(settings('mail.host'))->toBe('smtp2.example.edu');
});

it('sends a test email with the saved settings', function () {
    Mail::fake();
    $this->actingAs(superAdmin());

    Livewire::test(ManageSettings::class)->callAction('testMail')->assertNotified(__('settings::settings.notifications.mail_sent'));
});

it('shows visitors the maintenance message, signed-in users the website (FR-SET-08)', function () {
    settings()->save('maintenance', ['enabled' => true, 'message' => ['en' => 'Back at 10 pm']], translatable: ['message']);

    $this->get('/')->assertStatus(503)->assertSee('Back at 10 pm');
    $this->get('/admin/login')->assertOk();

    // How long and whom to write to, as DIU's maintenance page (the default theme) shows them
    settings()->save('maintenance', ['duration' => ['en' => 'Back within a few hours'], 'help_email' => 'info@example.edu'], translatable: ['message', 'duration']);
    $this->get('/')->assertStatus(503)
        ->assertSeeInOrder(['Under Maintenance', 'Back at 10 pm', 'Expected Duration', 'Back within a few hours', 'Need Help?', 'info@example.edu'])
        ->assertDontSee('site-header', false);                               // no menus while under maintenance
    $this->actingAs(makeUser())->get('/')->assertOk();
});

it('brands the admin panel and resets to neutral (FR-SET-13)', function () {
    settings()->save('admin_branding', ['panel_name' => ['en' => 'DIU Admin'], 'login_message' => ['en' => 'Staff only']], translatable: ['panel_name', 'login_message']);

    $this->get('/admin/login')->assertOk()->assertSee('DIU Admin')->assertSee('Staff only');
    expect(AdminBrandingSettings::readableOnWhite('#034EA2'))->toBeTrue()
        ->and(AdminBrandingSettings::readableOnWhite('#FFE600'))->toBeFalse();

    $this->actingAs(makeUser(['Update:Settings']));
    Livewire::test(ManageSettings::class)->assertActionHidden('resetBranding');

    $this->actingAs(superAdmin());
    Livewire::test(ManageSettings::class)->callAction('resetBranding');

    expect(Setting::where('group', 'admin_branding')->count())->toBe(0)
        ->and(settings('admin_branding.primary_color'))->toBe(AdminBrandingSettings::NEUTRAL_COLOR);
});

it('drops a module\'s settings when it is uninstalled with its data', function () {
    settings()->save('faq', ['per_page' => 50]);
    event(new ModuleUninstalling(Manifest::fromDirectory(base_path('Modules/Faq')), deleteData: true));

    expect(Setting::where('group', 'faq')->count())->toBe(0)
        ->and(settings('faq.per_page'))->toBe(20);
});
