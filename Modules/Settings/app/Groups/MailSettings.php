<?php

namespace Modules\Settings\Groups;

use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Modules\Settings\Support\SettingsAccess;
use Platform\Settings\ModuleSettings;

/** FR-SET-07: SMTP server; off = the server's .env mail settings are used. */
class MailSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'mail';
    }

    public function label(): string
    {
        return __('settings::settings.groups.mail');
    }

    public function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedEnvelope;
    }

    public function sort(): int
    {
        return 60;
    }

    public function encrypted(): array
    {
        return ['password'];
    }

    public function private(): array
    {
        return ['enabled', 'host', 'port', 'username', 'password', 'encryption'];
    }

    public function defaults(): array
    {
        return ['enabled' => false, 'port' => 587, 'encryption' => 'tls'];
    }

    public function canAccess(User $user): bool
    {
        return SettingsAccess::advanced($user);
    }

    public function schema(): array
    {
        $smtp = fn (Get $get): bool => (bool) $get('enabled');

        return [
            Toggle::make('enabled')->label(__('settings::settings.fields.mail_enabled'))
                ->helperText(__('settings::settings.help.mail_enabled'))->live()->columnSpanFull(),
            TextInput::make('host')->label(__('settings::settings.fields.host'))->required($smtp)->visible($smtp)->maxLength(255),
            TextInput::make('port')->label(__('settings::settings.fields.port'))->numeric()->integer()->required($smtp)->visible($smtp),
            TextInput::make('username')->label(__('settings::settings.fields.username'))->visible($smtp)->maxLength(255),
            TextInput::make('password')->label(__('settings::settings.fields.password'))->password()->revealable()
                ->visible($smtp)->helperText(__('settings::settings.help.secret_kept'))->maxLength(255),
            Select::make('encryption')->label(__('settings::settings.fields.encryption'))
                ->options(['tls' => 'TLS', 'ssl' => 'SSL', 'none' => __('settings::settings.fields.none')])
                ->visible($smtp)->required($smtp),
            TextInput::make('from_address')->label(__('settings::settings.fields.from_address'))->email()->maxLength(150),
            TextInput::make('from_name')->label(__('settings::settings.fields.from_name'))->maxLength(150),
        ];
    }
}
