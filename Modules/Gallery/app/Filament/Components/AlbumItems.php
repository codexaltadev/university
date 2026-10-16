<?php

namespace Modules\Gallery\Filament\Components;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Str;
use Modules\Gallery\Models\Album;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Media\MediaFolder;
use Platform\Media\MediaItem;
use Platform\Media\MediaUsage;

/**
 * The album's pictures and uploaded videos (FR-GAL-01, BRL-GAL-01): Media Library items, never
 * copies — saved as the album's media uses of "items", in the dragged order, each with an
 * optional caption for this album. "Pick from the library" adds many at once (uploading there
 * too), "Add a folder" adds every picture and video of a Media Library folder.
 */
class AlbumItems
{
    public const KINDS = ['image', 'video'];

    public static function make(): Repeater
    {
        return Repeater::make(Album::ITEMS)
            ->label(__('gallery::gallery.fields.items'))
            ->helperText(__('gallery::gallery.help.items'))
            ->schema([
                MediaPicker::make('media')->hiddenLabel()->storeIds()->required(),
                Grid::make(2)->schema(TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                    TextInput::make("caption.{$locale}")->label(__('gallery::gallery.fields.caption')." ({$language})")
                        ->helperText($isDefault ? __('gallery::gallery.help.caption') : null)->maxLength(255),
                ])),
            ])
            ->grid(2)->reorderable()->collapsible()->collapsed()->defaultItems(0)
            ->itemLabel(fn (array $state): ?string => collect((array) ($state['caption'] ?? []))->filter()->first()
                ?? (($id = $state['media'] ?? null) ? MediaItem::query()->find($id)?->title : null))
            ->addActionLabel(__('gallery::gallery.add_one'))
            ->hintActions([
                Action::make('pick')->label(__('gallery::gallery.pick'))->icon('heroicon-o-photo')
                    ->schema([MediaPicker::make('ids')->label(__('gallery::gallery.fields.items'))->storeIds()->multiple()->required()])
                    ->action(fn (array $data, Repeater $component) => static::append($component, (array) $data['ids'])),
                Action::make('folder')->label(__('gallery::gallery.add_folder'))->icon('heroicon-o-folder-plus')
                    ->schema([Select::make('folder')->label(__('gallery::gallery.folder'))->options(fn (): array => MediaFolder::options(auth()->user()))->searchable()->required()])
                    ->action(fn (array $data, Repeater $component) => static::append($component, MediaItem::query()
                        ->visibleTo(auth()->user())->where('is_active', true)->where('folder_id', $data['folder'])
                        ->whereIn('kind', self::KINDS)->orderBy('id')->pluck('id')->all())),
            ])
            ->dehydrated(false)
            ->afterStateHydrated(function (Repeater $component, ?Album $record): void {
                if ($record?->exists) {
                    $component->state($record->mediaUsagesIn(Album::ITEMS)->mapWithKeys(fn (MediaUsage $u): array => [(string) Str::uuid() => [
                        'media' => $u->media_item_id,
                        'caption' => (array) ($u->meta['caption'] ?? []),
                    ]])->all());
                }
            })
            ->saveRelationshipsUsing(fn (Album $record, ?array $state) => static::save($record, (array) $state));
    }

    /** Add items at the end; ones already in the album are not added twice. */
    public static function append(Repeater $component, array $ids): void
    {
        $state = (array) $component->getState();
        $have = array_map(fn (array $row): int => (int) ($row['media'] ?? 0), $state);
        foreach (array_map('intval', $ids) as $id) {
            if ($id > 0 && ! in_array($id, $have, true)) {
                $state[(string) Str::uuid()] = ['media' => $id, 'caption' => []];
                $have[] = $id;
            }
        }
        $component->state($state);
    }

    /** @param array<array-key, array{media?: mixed, caption?: array}> $rows */
    public static function save(Album $album, array $rows): void
    {
        $rows = array_values(array_filter($rows, fn (array $row): bool => filled($row['media'] ?? null)));
        $kinds = MediaItem::query()->whereKey(array_map(fn (array $r): int => (int) $r['media'], $rows))->pluck('kind', 'id');
        $ids = [];
        $meta = [];
        foreach ($rows as $row) {
            $id = (int) $row['media'];
            if (! in_array($kinds[$id] ?? null, self::KINDS, true)) {
                continue;   // only pictures and videos belong in an album
            }
            $caption = array_filter((array) ($row['caption'] ?? []), fn ($c): bool => filled($c));
            $ids[] = $id;
            $meta[$id] = $caption !== [] ? ['caption' => $caption] : [];
        }
        $album->syncMedia(Album::ITEMS, $ids, $meta);
    }
}
