<?php

namespace Modules\ThemeManager\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Platform\Theme\Theme;
use Platform\Theme\Themes;
use RuntimeException;

/**
 * Appearance › Themes (C-06, FR-THEME-01/02/03): installed themes with screenshot,
 * one active; activate, preview (only for this admin), install or update from a
 * ZIP, uninstall (never the active or a bundled theme).
 */
class ManageThemes extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static ?int $navigationSort = 50;

    protected static ?string $slug = 'themes';

    protected string $view = 'thememanager::themes';

    public static function getNavigationGroup(): ?string
    {
        return 'Appearance';
    }

    public static function getNavigationLabel(): string
    {
        return __('thememanager::theme.themes.title');
    }

    public function getTitle(): string
    {
        return __('thememanager::theme.themes.title');
    }

    public static function canAccess(): bool
    {
        return Themes::allows(Auth::user(), 'View:Theme');
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    /** @return Collection<int, Theme> */
    public function getThemes(): Collection
    {
        return $this->themes()->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('install')->label(__('thememanager::theme.actions.install'))
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->visible(fn (): bool => Themes::allows(Auth::user(), 'Install:Theme'))
                ->modalDescription(__('thememanager::theme.help.install'))
                ->schema([
                    FileUpload::make('package')->label(__('thememanager::theme.fields.package'))
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed', 'application/octet-stream'])
                        ->disk('local')->directory('theme-uploads')->maxSize(30 * 1024)->required()->storeFiles(false),
                ])
                ->action(function (array $data): void {
                    $file = $data['package'];
                    $path = $file instanceof TemporaryUploadedFile ? $file->getRealPath() : Storage::disk('local')->path((string) $file);

                    try {
                        $theme = $this->themes()->install($path);
                    } catch (RuntimeException $e) {
                        Notification::make()->danger()->title(__('thememanager::theme.notifications.install_failed'))
                            ->body(nl2br(e($e->getMessage())))->persistent()->send();

                        return;
                    }

                    Notification::make()->success()->title(__('thememanager::theme.notifications.installed', ['theme' => $theme->name, 'version' => $theme->version]))->send();
                }),
        ];
    }

    public function activateAction(): Action
    {
        return Action::make('activate')->label(__('thememanager::theme.actions.activate'))
            ->icon(Heroicon::OutlinedCheckCircle)->color('primary')->size('sm')
            ->requiresConfirmation()
            ->modalDescription(__('thememanager::theme.help.activate'))
            ->visible(fn (): bool => Themes::allows(Auth::user(), 'Activate:Theme'))
            ->action(function (array $arguments): void {
                try {
                    $theme = $this->themes()->activate((string) ($arguments['slug'] ?? ''));
                } catch (RuntimeException $e) {
                    Notification::make()->danger()->title(__('thememanager::theme.notifications.activate_failed'))
                        ->body(nl2br(e($e->getMessage())))->persistent()->send();

                    return;
                }

                $this->themes()->startPreview(null);
                Notification::make()->success()->title(__('thememanager::theme.notifications.activated', ['theme' => $theme->name]))->send();
            });
    }

    public function previewAction(): Action
    {
        return Action::make('preview')->label(__('thememanager::theme.actions.preview'))
            ->icon(Heroicon::OutlinedEye)->color('gray')->size('sm')
            ->visible(fn (): bool => Themes::allows(Auth::user(), 'Activate:Theme'))
            ->action(function (array $arguments): void {
                $theme = $this->themes()->find((string) ($arguments['slug'] ?? ''));
                abort_if($theme === null, 404);

                $this->themes()->startPreview($theme->slug);
                $this->redirect(url('/'));
            });
    }

    public function uninstallAction(): Action
    {
        return Action::make('uninstall')->label(__('thememanager::theme.actions.uninstall'))
            ->icon(Heroicon::OutlinedTrash)->color('danger')->size('sm')
            ->requiresConfirmation()
            ->modalDescription(__('thememanager::theme.help.uninstall'))
            ->visible(fn (): bool => Themes::allows(Auth::user(), 'Install:Theme'))
            ->action(function (array $arguments): void {
                try {
                    $this->themes()->uninstall((string) ($arguments['slug'] ?? ''));
                } catch (RuntimeException $e) {
                    Notification::make()->danger()->title($e->getMessage())->send();

                    return;
                }

                Notification::make()->success()->title(__('thememanager::theme.notifications.uninstalled'))->send();
            });
    }

    protected function themes(): Themes
    {
        return app(Themes::class);
    }
}
