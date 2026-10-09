<?php

/*
| Phase 2.4 — Localization (C-15): languages, default rules, fallback, interface
| text overrides, admin language (FR-LANG-01/02/03/05/08, BRL-LANG-01/02, TC-LANG-03).
*/

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Localization\Database\Seeders\LocalizationDatabaseSeeder;
use Modules\Localization\Filament\Pages\InterfaceTexts;
use Modules\Localization\Filament\Resources\Languages\LanguageResource;
use Modules\Localization\Filament\Resources\Languages\Pages\ManageLanguages;
use Modules\User\Filament\Pages\EditProfile;
use Platform\Assignments\ManualPerson;
use Platform\Localization\InterfaceTexts as Texts;
use Platform\Localization\Language;
use Platform\Localization\LanguageRuleViolation;
use Platform\Localization\Languages;
use Platform\Localization\Numerals;
use Platform\Modules\ModulePermissions;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Localization');
    Permission::findOrCreate('Update:InterfaceText', 'web');
});

function languages(): Languages
{
    return app(Languages::class);
}

it('starts from config, then reads the languages table (FR-LANG-01)', function () {
    expect(languages()->codes())->toBe(['en', 'bn'])->and(languages()->default())->toBe('en');

    (new LocalizationDatabaseSeeder)->run();
    (new LocalizationDatabaseSeeder)->run();   // safe twice

    Language::create(['code' => 'AR', 'name' => 'Arabic', 'native_name' => 'العربية', 'direction' => 'rtl', 'is_active' => false, 'sort_order' => 5]);

    expect(Language::count())->toBe(3)
        ->and(languages()->codes())->toBe(['en', 'bn', 'ar'])          // code lower-cased, cache refreshed
        ->and(languages()->activeCodes())->toBe(['en', 'bn'])
        ->and(languages()->labels())->toBe(['en' => 'English', 'bn' => 'বাংলা']);
});

it('keeps exactly one default, always active and never deleted (BRL-LANG-01)', function () {
    (new LocalizationDatabaseSeeder)->run();
    $en = Language::where('code', 'en')->first();
    $bn = Language::where('code', 'bn')->first();

    expect(fn () => $en->update(['is_active' => false]))->toThrow(LanguageRuleViolation::class)
        ->and(fn () => $en->delete())->toThrow(LanguageRuleViolation::class)
        ->and(fn () => $en->refresh()->update(['is_default' => false]))->toThrow(LanguageRuleViolation::class);

    $bn->update(['is_default' => true]);

    expect($en->refresh()->is_default)->toBeFalse()
        ->and(Language::where('is_default', true)->pluck('code')->all())->toBe(['bn'])
        ->and(languages()->default())->toBe('bn')
        ->and(config('app.fallback_locale'))->toBe('bn');
});

it('falls back to the default language for missing translations (FR-LANG-05, AC-LANG-01)', function () {
    (new LocalizationDatabaseSeeder)->run();
    $person = ManualPerson::create(['name' => ['en' => 'Dr. Rahman']]);

    app()->setLocale('bn');
    expect($person->name)->toBe('Dr. Rahman');                                // content: English fallback
    expect(__('frontend.read_more'))->toBe('আরও পড়ুন');                      // interface: Bangla
});

it('shows numbers in native digits only when the language asks (FR-LANG-08)', function () {
    (new LocalizationDatabaseSeeder)->run();
    expect(Numerals::localize(2026, 'bn'))->toBe('2026');

    Language::where('code', 'bn')->first()->update(['use_native_digits' => true]);
    expect(Numerals::localize('12/10/2026', 'bn'))->toBe('১২/১০/২০২৬')
        ->and(Numerals::localize(5, 'en'))->toBe('5');
});

it('lets admins add a language and change the default on screen', function () {
    (new LocalizationDatabaseSeeder)->run();
    $this->actingAs(makeUser())->get(LanguageResource::getUrl('index'))->assertForbidden();

    $this->actingAs(makeUser(['ViewAny:Language', 'Create:Language', 'Update:Language', 'Delete:Language']));
    $this->get(LanguageResource::getUrl('index'))->assertOk();

    Livewire::test(ManageLanguages::class)
        ->callAction('create', data: ['code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية', 'direction' => 'rtl', 'is_active' => true])
        ->assertHasNoFormErrors();

    $ar = Language::where('code', 'ar')->firstOrFail();
    $en = Language::where('code', 'en')->first();

    Livewire::test(ManageLanguages::class)
        ->assertTableActionHidden('delete', $en)
        ->callTableAction('makeDefault', $ar);

    expect($ar->refresh()->is_default)->toBeTrue()
        ->and($en->refresh()->is_default)->toBeFalse();

    Livewire::test(ManageLanguages::class)->callTableAction('delete', $en);
    expect(Language::where('code', 'en')->exists())->toBeFalse();
});

it('lets admins change interface texts per language (FR-LANG-03, TC-LANG-03)', function () {
    $this->actingAs(makeUser(['ViewAny:Language']))->get(InterfaceTexts::getUrl())->assertForbidden();
    $this->actingAs(makeUser(['Update:InterfaceText']));
    $this->get(InterfaceTexts::getUrl())->assertOk()->assertSee('read_more');

    Livewire::test(InterfaceTexts::class)
        ->callTableAction('edit', '*::read_more', data: ['bn' => 'বিস্তারিত'])
        ->assertHasNoTableActionErrors();

    expect(__('frontend.read_more', [], 'bn'))->toBe('বিস্তারিত')
        ->and(__('frontend.read_more', [], 'en'))->toBe('Read more');    // empty: the file text stays

    app(Texts::class)->save('*', 'read_more', []);
    expect(__('frontend.read_more', [], 'bn'))->toBe('আরও পড়ুন');
});

it('shows the admin panel in the user\'s language, set on the profile', function () {
    $user = makeUser(['ViewAny:Language']);
    $this->actingAs($user);

    Livewire::test(EditProfile::class)
        ->fillForm(['locale' => 'bn'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->refresh()->locale)->toBe('bn');
    $this->get(LanguageResource::getUrl('index'))->assertOk()->assertSee('নিজস্ব অঙ্ক');
});
