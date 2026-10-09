<?php

namespace Modules\Media\Filament\Resources\MediaItems;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Platform\Exceptions\ParentHasActiveChildren;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Media\MediaFolder;
use Platform\Media\MediaItem;
use Platform\Media\MediaLibrary;
use Platform\Media\MediaRejected;
use Platform\Media\MediaUsage;

/** Actions of Site › Media Library (FR-MEDIA-03/07/08/10/16). */
class MediaActions
{
    /** Drag & drop up to 50 files at once, with progress (FR-MEDIA-03). */
    public static function upload(): Action
    {
        return Action::make('upload')
            ->label(__('media::media.actions.upload'))
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->authorize('create', MediaItem::class)
            ->modalWidth('3xl')
            ->schema([
                FileUpload::make('files')->label(__('media::media.fields.files'))
                    ->multiple()->maxFiles(50)->storeFiles(false)->required()
                    ->maxSize(app(MediaLibrary::class)->maxUploadKilobytes())
                    ->panelLayout('grid')
                    ->helperText(fn (): string => __('media::media.help.allowed', [
                        'types' => implode(', ', app(MediaLibrary::class)->allowedExtensions()),
                        'max' => app(MediaLibrary::class)->maxUploadKilobytes() / 1024,
                    ])),
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    TextInput::make("alt_text.{$locale}")->label(__('media::media.fields.alt_text'))
                        ->helperText(__('media::media.help.alt_text_upload'))->maxLength(255),
                ]),
                Select::make('folder_id')->label(__('media::media.fields.folder'))->options(fn (): array => MediaFolder::options(Auth::user()))
                    ->default(fn ($livewire): ?int => $livewire->folder ?? null)
                    ->placeholder(__('media::media.no_folder')),
                Toggle::make('is_shared')->label(__('media::media.fields.shared'))->helperText(__('media::media.help.shared')),
                Toggle::make('is_private')->label(__('media::media.fields.private'))->helperText(__('media::media.help.private')),
            ])
            ->action(function (array $data): void {
                $library = app(MediaLibrary::class);
                $stored = 0;
                $problems = [];
                $duplicates = [];

                foreach ($data['files'] as $file) {
                    if ($existing = $library->duplicateOf($file)) {
                        $duplicates[] = $file->getClientOriginalName().' → '.($existing->title ?: '#'.$existing->id);
                    }

                    try {
                        $library->store($file, collect($data)->only(['folder_id', 'is_shared', 'is_private', 'alt_text'])->all());
                        $stored++;
                    } catch (MediaRejected $e) {
                        $problems[] = $e->getMessage();
                    }
                }

                if ($stored > 0) {
                    Notification::make()->success()->title(__('media::media.notifications.uploaded', ['count' => $stored]))->send();
                }

                if ($duplicates !== []) {
                    Notification::make()->warning()->title(__('media::media.notifications.duplicates'))
                        ->body(implode("\n", $duplicates))->persistent()->send();
                }

                if ($problems !== []) {
                    Notification::make()->danger()->title(__('media::media.notifications.rejected', ['count' => count($problems)]))
                        ->body(implode("\n", $problems))->persistent()->send();
                }
            });
    }

    /** New file, same item, same /media/{id} address; every usage keeps working (FR-MEDIA-07). */
    public static function replace(): Action
    {
        return Action::make('replace')
            ->label(__('media::media.actions.replace'))
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('gray')
            ->authorize(fn (MediaItem $record): bool => Auth::user()->can('update', $record))
            ->schema([
                FileUpload::make('file')->label(__('media::media.fields.file'))->storeFiles(false)->required()
                    ->maxSize(app(MediaLibrary::class)->maxUploadKilobytes()),
            ])
            ->modalDescription(__('media::media.help.replace'))
            ->action(function (MediaItem $record, array $data): void {
                try {
                    app(MediaLibrary::class)->replace($record, $data['file']);
                    Notification::make()->success()->title(__('media::media.notifications.replaced'))->send();
                } catch (MediaRejected $e) {
                    Notification::make()->danger()->title($e->getMessage())->send();
                }
            });
    }

    public static function download(): Action
    {
        return Action::make('download')
            ->label(__('media::media.actions.download'))
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->url(fn (MediaItem $record): ?string => $record->url(), shouldOpenInNewTab: true);
    }

    public static function moveToFolder(): BulkAction
    {
        return BulkAction::make('move')
            ->label(__('media::media.actions.move'))
            ->icon(Heroicon::OutlinedFolderArrowDown)
            ->schema([
                Select::make('folder_id')->label(__('media::media.fields.folder'))->options(fn (): array => MediaFolder::options(Auth::user()))
                    ->placeholder(__('media::media.no_folder')),
            ])
            ->action(function (Collection $records, array $data): void {
                $records->filter(fn (MediaItem $item): bool => Auth::user()->can('update', $item))
                    ->each(fn (MediaItem $item) => $item->update(['folder_id' => $data['folder_id'] ?: null]));
            })
            ->deselectRecordsAfterCompletion();
    }

    /** Folders: new, rename, delete (FR-MEDIA-08). A folder with files or sub-folders stays. */
    public static function folders(): ActionGroup
    {
        $canManage = fn (): bool => static::canManageFolders(Auth::user());

        return ActionGroup::make([
            Action::make('newFolder')
                ->label(__('media::media.actions.new_folder'))
                ->icon(Heroicon::OutlinedFolderPlus)
                ->visible($canManage)
                ->schema([
                    TextInput::make('name')->label(__('media::media.fields.name'))->required()->maxLength(191),
                    Select::make('parent_id')->label(__('media::media.fields.parent'))->options(fn (): array => MediaFolder::options(Auth::user()))
                        ->default(fn ($livewire): ?int => $livewire->folder ?? null)
                        ->placeholder(__('media::media.no_folder')),
                ])
                ->action(function (array $data): void {
                    [$type, $id] = app(MediaLibrary::class)->defaultScope(Auth::user());
                    MediaFolder::query()->create([...$data, 'scope_type' => $type, 'scope_id' => $id]);
                    Notification::make()->success()->title(__('media::media.notifications.folder_saved'))->send();
                }),
            Action::make('renameFolder')
                ->label(__('media::media.actions.rename_folder'))
                ->icon(Heroicon::OutlinedPencilSquare)
                ->visible($canManage)
                ->schema([
                    Select::make('folder_id')->label(__('media::media.fields.folder'))->options(fn (): array => MediaFolder::options(Auth::user()))
                        ->default(fn ($livewire): ?int => $livewire->folder ?? null)->required(),
                    TextInput::make('name')->label(__('media::media.fields.name'))->required()->maxLength(191),
                ])
                ->action(function (array $data): void {
                    MediaFolder::query()->findOrFail($data['folder_id'])->update(['name' => $data['name']]);
                    Notification::make()->success()->title(__('media::media.notifications.folder_saved'))->send();
                }),
            Action::make('deleteFolder')
                ->label(__('media::media.actions.delete_folder'))
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->visible($canManage)
                ->schema([
                    Select::make('folder_id')->label(__('media::media.fields.folder'))->options(fn (): array => MediaFolder::options(Auth::user()))
                        ->default(fn ($livewire): ?int => $livewire->folder ?? null)->required(),
                ])
                ->action(function (array $data, $livewire): void {
                    try {
                        $folder = MediaFolder::query()->findOrFail($data['folder_id']);
                        $folder->delete();

                        if (($livewire->folder ?? null) === $folder->id) {
                            $livewire->openFolder($folder->parent_id);
                        }
                        Notification::make()->success()->title(__('media::media.notifications.folder_deleted'))->send();
                    } catch (ParentHasActiveChildren) {
                        Notification::make()->danger()->title(__('media::media.notifications.folder_not_empty'))->send();
                    }
                }),
        ])
            ->label(__('media::media.folders'))
            ->icon(Heroicon::OutlinedFolder)
            ->button()
            ->color('gray');
    }

    public static function canManageFolders(?User $user): bool
    {
        return $user !== null && ($user->isSuperAdmin() || $user->can('Update:MediaItem'));
    }

    /**
     * Where the item is used, with links (FR-MEDIA-09, AC-MEDIA-02).
     *
     * @return list<array{label: string, url: ?string}>
     */
    public static function usages(MediaItem $item): array
    {
        return $item->usages()->with('usable')->get()->map(function (MediaUsage $usage): array {
            $record = $usage->usable;

            if ($record === null) {
                return ['label' => "{$usage->usable_type} #{$usage->usable_id}", 'url' => null];
            }

            $resource = Filament::getCurrentOrDefaultPanel()->getModelResource($record);
            $title = $record->getAttribute($record::$titleAttribute ?? 'name') ?? ($record->getAttribute('key') ? "{$record->group}.{$record->key}" : '#'.$record->getKey());
            $label = ($resource ? $resource::getModelLabel() : class_basename($record)).': '.$title.' ('.$usage->field.')';

            $url = null;
            if ($resource && $resource::hasPage('edit') && $resource::canEdit($record)) {
                $url = $resource::getUrl('edit', ['record' => $record]);
            }

            return ['label' => $label, 'url' => $url];
        })->all();
    }

    public static function usageHtml(MediaItem $item): HtmlString
    {
        $usages = static::usages($item);

        if ($usages === []) {
            return new HtmlString(e(__('media::media.not_used')));
        }

        return new HtmlString('<ul class="list-disc ps-5 space-y-1">'.collect($usages)->map(fn (array $u): string => '<li>'
            .($u['url'] ? '<a class="text-primary-600 underline" href="'.e($u['url']).'">'.e($u['label']).'</a>' : e($u['label']))
            .'</li>')->implode('').'</ul>');
    }

    /** Delete warning: soft delete still allowed, usages listed (FR-MEDIA-10). */
    public static function deleteWarning(MediaItem $item): HtmlString
    {
        if ($item->usage_count === 0) {
            return new HtmlString(e(__('media::media.help.delete')));
        }

        return new HtmlString('<p class="mb-2">'.e(__('media::media.help.delete_used', ['count' => $item->usage_count])).'</p>'.static::usageHtml($item)->toHtml());
    }
}
