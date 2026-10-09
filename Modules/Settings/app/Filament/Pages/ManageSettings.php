<?php

namespace Modules\Settings\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use Modules\Settings\Groups\AdminBrandingSettings;
use Modules\Settings\Support\MailConfig;
use Modules\Settings\Support\SettingsAccess;
use Platform\Settings\ModuleSettings;
use Platform\Settings\Settings;
use Throwable;

/**
 * Site › Settings (C-05, UI/UX Part A §7): one tab per registered group — core
 * groups first, then each active module's tab (FR-SET-10, AC-SET-02).
 */
class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'settings';

    /** @var array<string, array<string, mixed>> group => values */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return 'Site';
    }

    public static function getNavigationLabel(): string
    {
        return __('settings::settings.title');
    }

    public function getTitle(): string
    {
        return __('settings::settings.title');
    }

    public static function canAccess(): bool
    {
        return SettingsAccess::view(auth()->user());
    }

    public function mount(): void
    {
        $settings = app(Settings::class);
        $data = [];

        foreach ($this->groups() as $definition) {
            $values = $settings->group($definition->group());

            foreach ($definition->encrypted() as $key) {
                $values[$key] = null;   // never sent to the browser; empty = keep
            }

            $data[$definition->group()] = $values;
        }

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->disabled(! SettingsAccess::update(auth()->user()))
            ->components([
                Tabs::make('settings')
                    ->persistTabInQueryString()
                    ->tabs(collect($this->groups())->map(fn (ModuleSettings $definition): Tab => Tab::make($definition->label())
                        ->key('tab-'.$definition->group())
                        ->icon($definition->icon())
                        ->schema([
                            Group::make($definition->schema())->statePath($definition->group())->columns(2),
                        ]))->all()),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label(__('settings::settings.actions.save'))->submit('save')->keyBindings(['mod+s'])
                            ->visible(fn (): bool => SettingsAccess::update(auth()->user())),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(SettingsAccess::update(auth()->user()), 403);

        $state = $this->form->getState();
        $settings = app(Settings::class);
        $changed = 0;

        foreach ($this->groups() as $definition) {
            $values = $state[$definition->group()] ?? [];

            foreach ($definition->encrypted() as $key) {
                if (blank($values[$key] ?? null)) {
                    unset($values[$key]);   // empty secret field: keep the saved one
                }
            }

            $changed += count($settings->saveFor($definition, $values));
        }

        Notification::make()->success()
            ->title($changed > 0 ? __('settings::settings.notifications.saved') : __('settings::settings.notifications.unchanged'))
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testMail')
                ->label(__('settings::settings.actions.test_mail'))
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('gray')
                ->visible(fn (): bool => SettingsAccess::advanced(auth()->user()))
                ->requiresConfirmation()
                ->modalDescription(fn (): string => __('settings::settings.help.test_mail', ['email' => auth()->user()->email]))
                ->action(function (): void {
                    try {
                        MailConfig::apply();
                        Mail::raw(__('settings::settings.test_mail_body', ['site' => settings('general.site_name')]),
                            fn ($message) => $message->to(auth()->user()->email)->subject(__('settings::settings.test_mail_subject')));

                        Notification::make()->success()->title(__('settings::settings.notifications.mail_sent'))->send();
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title(__('settings::settings.notifications.mail_failed'))->body($e->getMessage())->persistent()->send();
                    }
                }),
            Action::make('resetBranding')
                ->label(__('settings::settings.actions.reset_branding'))
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('gray')
                ->visible(fn (): bool => (bool) auth()->user()?->isSuperAdmin())
                ->requiresConfirmation()
                ->modalDescription(__('settings::settings.help.reset_branding'))
                ->action(function (): void {
                    app(Settings::class)->forgetGroup(app(AdminBrandingSettings::class)->group());
                    $this->redirect(static::getUrl(['tab' => 'tab-admin_branding']));
                }),
        ];
    }

    /** @return list<ModuleSettings> tabs this user may see */
    protected function groups(): array
    {
        $user = auth()->user();

        return array_values(array_filter(app(Settings::class)->definitions(), fn (ModuleSettings $d): bool => $user !== null && $d->canAccess($user)));
    }
}
