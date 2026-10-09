<?php

namespace Modules\ModuleManager\Filament\Resources\Modules;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Platform\Models\CoreModule;
use Platform\Modules\ModuleManager;
use Platform\Modules\ModuleOperationFailed;
use Platform\Modules\ModuleRegistry;

/**
 * Lifecycle actions of the Module Manager screen. Every refusal shows the
 * Module Manager's readable reasons (FR-MODM-04); nothing is half-done.
 */
final class ModuleActions
{
    public static function install(): Action
    {
        return Action::make('installFromZip')
            ->label(__('modulemanager::manager.actions.install'))
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->visible(fn (): bool => Auth::user()?->can('create', CoreModule::class) ?? false)
            ->modalDescription(__('modulemanager::manager.help.install'))
            ->schema([
                FileUpload::make('package')
                    ->label(__('modulemanager::manager.fields.package'))
                    ->disk('local')
                    ->directory('module-uploads')
                    ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                    ->maxSize((int) (config('platform.modules.max_zip_size') / 1024))
                    ->required(),
            ])
            ->action(function (array $data): void {
                $path = Storage::disk('local')->path($data['package']);

                try {
                    $module = app(ModuleManager::class)->installZip($path);
                    self::success(__('modulemanager::manager.done.installed', ['name' => $module->name, 'version' => $module->version]));
                } catch (ModuleOperationFailed $e) {
                    self::refused($e);
                } finally {
                    Storage::disk('local')->delete($data['package']);
                }
            });
    }

    public static function activate(): Action
    {
        return Action::make('activate')
            ->label(__('modulemanager::manager.actions.activate'))
            ->icon(Heroicon::OutlinedPlay)
            ->color('success')
            ->visible(fn (CoreModule $record): bool => ! self::isActive($record) && $record->status !== CoreModule::STATUS_UNINSTALLED && (Auth::user()?->can('update', $record) ?? false))
            ->requiresConfirmation()
            ->action(fn (CoreModule $record) => self::run(fn () => app(ModuleManager::class)->activate($record->slug), __('modulemanager::manager.done.activated', ['name' => $record->name])));
    }

    public static function deactivate(): Action
    {
        return Action::make('deactivate')
            ->label(__('modulemanager::manager.actions.deactivate'))
            ->icon(Heroicon::OutlinedPause)
            ->color('warning')
            ->visible(fn (CoreModule $record): bool => self::isActive($record) && $record->type !== 'core' && (Auth::user()?->can('update', $record) ?? false))
            ->requiresConfirmation()
            ->modalDescription(__('modulemanager::manager.help.deactivate'))
            ->action(fn (CoreModule $record) => self::run(fn () => app(ModuleManager::class)->deactivate($record->slug), __('modulemanager::manager.done.deactivated', ['name' => $record->name])));
    }

    /** FR-MODM-11: keep or delete data; deleting needs the slug typed. */
    public static function uninstall(): Action
    {
        return Action::make('uninstall')
            ->label(__('modulemanager::manager.actions.uninstall'))
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->visible(fn (CoreModule $record): bool => ! self::isActive($record) && $record->status !== CoreModule::STATUS_UNINSTALLED && (Auth::user()?->can('delete', $record) ?? false))
            ->modalDescription(__('modulemanager::manager.help.uninstall'))
            ->schema(fn (CoreModule $record): array => [
                Radio::make('data')
                    ->label(__('modulemanager::manager.fields.data'))
                    ->options([
                        'keep' => __('modulemanager::manager.uninstall.keep'),
                        'delete' => __('modulemanager::manager.uninstall.delete'),
                    ])
                    ->default('keep')
                    ->live()
                    ->required(),
                TextInput::make('confirm')
                    ->label(__('modulemanager::manager.fields.confirm', ['slug' => $record->slug]))
                    ->visible(fn (Get $get): bool => $get('data') === 'delete')
                    ->required(fn (Get $get): bool => $get('data') === 'delete')
                    ->in([$record->slug])
                    ->validationMessages(['in' => __('modulemanager::manager.errors.confirm', ['slug' => $record->slug])]),
            ])
            ->action(fn (CoreModule $record, array $data) => self::run(
                fn () => app(ModuleManager::class)->uninstall($record->slug, deleteData: $data['data'] === 'delete'),
                __('modulemanager::manager.done.uninstalled', ['name' => $record->name]),
            ));
    }

    public static function isActive(CoreModule $record): bool
    {
        return app(ModuleRegistry::class)->isActive($record->slug);
    }

    protected static function run(\Closure $operation, string $success): void
    {
        try {
            $operation();
            self::success($success);
        } catch (ModuleOperationFailed $e) {
            self::refused($e);
        }
    }

    protected static function success(string $title): void
    {
        Notification::make()->title($title)->success()->send();
    }

    protected static function refused(ModuleOperationFailed $e): void
    {
        Notification::make()
            ->title(__('modulemanager::manager.errors.refused'))
            ->body(implode("\n", array_map(fn (string $error): string => '• '.$error, $e->errors)))
            ->danger()
            ->persistent()
            ->send();
    }
}
