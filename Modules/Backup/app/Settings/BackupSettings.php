<?php

namespace Modules\Backup\Settings;

use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Modules\Backup\Support\BackupDestinations;
use Platform\Settings\ModuleSettings;

/**
 * Settings › Backup (FR-BKP-01/04/05), Super Admins only: when the daily database
 * and weekly full backups run, how long backups are kept (at least 14 days),
 * where they go — this server, Amazon S3 / S3-compatible, or FTP, with their
 * access details and a connection test — and an optional archive password.
 */
class BackupSettings extends ModuleSettings
{
    public const DAYS = [0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];

    public function group(): string
    {
        return 'backup';
    }

    public function label(): string
    {
        return __('backup::backup.settings.tab');
    }

    public function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedCircleStack;
    }

    public function sort(): int
    {
        return 85;
    }

    public function encrypted(): array
    {
        return BackupDestinations::SECRETS;
    }

    /** Access details are for the server only, never the website or the API. */
    public function private(): array
    {
        return [...BackupDestinations::SECRETS, 's3_key', 's3_bucket', 's3_endpoint', 'ftp_host', 'ftp_username', 'gdrive_client_id', 'gdrive_account'];
    }

    public function defaults(): array
    {
        return [
            'database_time' => '01:00', 'full_day' => 0, 'full_time' => '01:30', 'retention_days' => 14,
            'destination' => 'local', 's3_region' => 'ap-southeast-1', 's3_path_style' => false,
            'ftp_port' => 21, 'ftp_ssl' => true, 'ftp_passive' => true,
        ];
    }

    public function canAccess(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function schema(): array
    {
        $is = fn (string $type): \Closure => fn (Get $get): bool => $get('destination') === $type;

        return [
            TimePicker::make('database_time')->label(__('backup::backup.settings.database_time'))->seconds(false)->required(),
            Select::make('full_day')->label(__('backup::backup.settings.full_day'))->options(self::DAYS)->required()->selectablePlaceholder(false),
            TimePicker::make('full_time')->label(__('backup::backup.settings.full_time'))->seconds(false)->required(),
            TextInput::make('retention_days')->label(__('backup::backup.settings.retention_days'))
                ->numeric()->minValue(14)->maxValue(3650)->required()->suffix(__('backup::backup.days')),

            Radio::make('destination')->label(__('backup::backup.settings.destination'))
                ->options(collect(BackupDestinations::TYPES)->mapWithKeys(fn (string $t): array => [$t => __("backup::backup.destinations.{$t}")])->all())
                ->descriptions(collect(BackupDestinations::TYPES)->mapWithKeys(fn (string $t): array => [$t => __("backup::backup.destinations.{$t}_help")])->all())
                ->required()->live()->columnSpanFull(),

            // Amazon S3 or S3-compatible
            Fieldset::make(__('backup::backup.destinations.s3'))->visible($is('s3'))->columnSpanFull()->schema([
                TextInput::make('s3_key')->label(__('backup::backup.settings.s3_key'))->required($is('s3'))->maxLength(200),
                TextInput::make('s3_secret')->label(__('backup::backup.settings.s3_secret'))->password()->revealable()
                    ->helperText(__('backup::backup.settings.secret_help'))->maxLength(200),
                TextInput::make('s3_bucket')->label(__('backup::backup.settings.s3_bucket'))->required($is('s3'))->maxLength(100),
                TextInput::make('s3_region')->label(__('backup::backup.settings.s3_region'))->placeholder('ap-southeast-1')->maxLength(50),
                TextInput::make('s3_endpoint')->label(__('backup::backup.settings.s3_endpoint'))->url()->maxLength(255)
                    ->helperText(__('backup::backup.settings.s3_endpoint_help')),
                TextInput::make('s3_root')->label(__('backup::backup.settings.root'))->placeholder('university-backups')->maxLength(150),
                Toggle::make('s3_path_style')->label(__('backup::backup.settings.s3_path_style'))
                    ->helperText(__('backup::backup.settings.s3_path_style_help')),
            ]),

            // Google Drive: the university's own OAuth client, then "Connect Google account"
            Fieldset::make(__('backup::backup.destinations.gdrive'))->visible($is('gdrive'))->columnSpanFull()->schema([
                Text::make(fn (): HtmlString => new HtmlString(__('backup::backup.gdrive.howto', ['uri' => e(route('backup.gdrive.callback'))])))->columnSpanFull(),
                TextInput::make('gdrive_client_id')->label(__('backup::backup.gdrive.client_id'))->required($is('gdrive'))->maxLength(255),
                TextInput::make('gdrive_client_secret')->label(__('backup::backup.gdrive.client_secret'))->password()->revealable()
                    ->helperText(__('backup::backup.settings.secret_help'))->maxLength(255),
                TextInput::make('gdrive_folder')->label(__('backup::backup.gdrive.folder'))->placeholder('University Backups')->maxLength(150),
                Text::make(fn (): string => ($account = settings('backup.gdrive_account')) && filled(settings()->group('backup')['gdrive_refresh_token'] ?? null)
                    ? __('backup::backup.gdrive.status_connected', ['account' => $account])
                    : __('backup::backup.gdrive.status_none'))
                    ->color(fn (): string => filled(settings()->group('backup')['gdrive_refresh_token'] ?? null) ? 'success' : 'warning')
                    ->columnSpanFull(),
                Actions::make([
                    Action::make('gdriveConnect')->label(__('backup::backup.gdrive.connect'))->icon(Heroicon::OutlinedLink)
                        ->url(fn (): string => route('backup.gdrive.connect')),
                    Action::make('gdriveDisconnect')->label(__('backup::backup.gdrive.disconnect'))->color('danger')->requiresConfirmation()
                        ->visible(fn (): bool => filled(settings()->group('backup')['gdrive_refresh_token'] ?? null))
                        ->action(function (): void {
                            settings()->saveFor($this, ['gdrive_refresh_token' => null, 'gdrive_account' => null]);
                            activity('backup')->event('disconnected')->log('Google Drive disconnected');
                            Notification::make()->success()->title(__('backup::backup.gdrive.disconnected'))->send();
                        }),
                ])->columnSpanFull(),
            ]),

            // FTP
            Fieldset::make(__('backup::backup.destinations.ftp'))->visible($is('ftp'))->columnSpanFull()->schema([
                TextInput::make('ftp_host')->label(__('backup::backup.settings.ftp_host'))->required($is('ftp'))->maxLength(255),
                TextInput::make('ftp_port')->label(__('backup::backup.settings.ftp_port'))->numeric()->minValue(1)->maxValue(65535),
                TextInput::make('ftp_username')->label(__('backup::backup.settings.ftp_username'))->required($is('ftp'))->maxLength(150),
                TextInput::make('ftp_password')->label(__('backup::backup.settings.ftp_password'))->password()->revealable()
                    ->helperText(__('backup::backup.settings.secret_help'))->maxLength(200),
                TextInput::make('ftp_root')->label(__('backup::backup.settings.root'))->placeholder('/backups')->maxLength(255),
                Toggle::make('ftp_ssl')->label(__('backup::backup.settings.ftp_ssl')),
                Toggle::make('ftp_passive')->label(__('backup::backup.settings.ftp_passive')),
            ]),

            Actions::make([
                Action::make('testDestination')->label(__('backup::backup.settings.test'))->icon(Heroicon::OutlinedSignal)->color('gray')
                    ->action(function (Get $get): void {
                        $values = collect(['destination', 's3_key', 's3_secret', 's3_bucket', 's3_region', 's3_endpoint', 's3_root', 's3_path_style',
                            'gdrive_client_id', 'gdrive_client_secret', 'gdrive_folder',
                            'ftp_host', 'ftp_port', 'ftp_username', 'ftp_password', 'ftp_root', 'ftp_ssl', 'ftp_passive'])
                            ->mapWithKeys(fn (string $key): array => [$key => $get($key)])->all();
                        $error = BackupDestinations::test($values);

                        $error === null
                            ? Notification::make()->success()->title(__('backup::backup.settings.test_ok'))->send()
                            : Notification::make()->danger()->title(__('backup::backup.settings.test_failed'))->body(e($error))->persistent()->send();
                    }),
            ])->visible(fn (Get $get): bool => $get('destination') !== 'local')->columnSpanFull(),

            TextInput::make('password')->label(__('backup::backup.settings.password'))->password()->revealable()
                ->helperText(__('backup::backup.settings.password_help'))->maxLength(100),
        ];
    }
}
