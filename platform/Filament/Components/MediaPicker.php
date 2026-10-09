<?php

namespace Platform\Filament\Components;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Platform\Localization\Languages;
use Platform\Media\MediaItem;
use Platform\Media\MediaLibrary;
use Platform\Media\MediaRejected;
use Platform\Media\MediaUsages;

/**
 * Pick files from the Media Library, or upload one right there (FR-MEDIA-04, AC-MEDIA-01):
 *
 *     MediaPicker::make('featured_image')->image()          // one picture
 *     MediaPicker::make('attachments')->multiple()           // several files, ordered
 *
 * Search by title or file name; only media the user may see (FR-MEDIA-14). On a
 * HasMediaUsage model the choice is saved as usage of that field. ->storeIds()
 * keeps the ids in the form data instead (settings).
 */
class MediaPicker extends Select
{
    protected bool $onlyImages = false;

    protected bool $storesIds = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchable()
            ->allowHtml()
            ->dehydrated(fn (MediaPicker $component): bool => $component->storesIds)
            ->options(fn (MediaPicker $component): array => $component->labels($component->query()->latest('id')->limit(30)->get()))
            ->getSearchResultsUsing(fn (MediaPicker $component, string $search): array => $component->labels($component->query()
                ->where(fn (Builder $q) => $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('media', fn (Builder $m) => $m->where('file_name', 'like', "%{$search}%")))
                ->latest('id')->limit(50)->get()))
            ->getOptionLabelsUsing(fn (MediaPicker $component, array $values): array => $component->labels(MediaItem::query()->whereIn('id', $values)->get()))
            ->getOptionLabelUsing(fn (MediaPicker $component, $value): ?string => ($item = MediaItem::query()->find($value)) ? $component->labels(collect([$item]))[$item->id] : null)
            ->afterStateHydrated(function (MediaPicker $component, ?Model $record): void {
                if ($component->storesIds || $record === null || ! $record->exists || ! method_exists($record, 'mediaIdsIn')) {
                    return;
                }

                $ids = $record->mediaIdsIn($component->getName());
                $component->state($component->isMultiple() ? $ids : ($ids[0] ?? null));
            })
            ->saveRelationshipsUsing(function (MediaPicker $component, Model $record, mixed $state): void {
                if (! $component->storesIds) {
                    app(MediaUsages::class)->sync($record, $component->getName(), (array) $state);
                }
            })
            ->createOptionForm(fn (MediaPicker $component): array => [
                FileUpload::make('file')->label(__('platform::media.file'))
                    ->storeFiles(false)
                    ->required()
                    ->maxSize(app(MediaLibrary::class)->maxUploadKilobytes())
                    ->acceptedFileTypes($component->onlyImages ? ['image/*'] : []),
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    TextInput::make("alt_text.{$locale}")->label(__('platform::media.alt_text'))
                        ->helperText(__('platform::media.alt_text_help'))->maxLength(255),
                ]),
                Toggle::make('is_shared')->label(__('platform::media.shared')),
            ])
            ->createOptionUsing(function (array $data): int {
                try {
                    return app(MediaLibrary::class)->store($data['file'], ['is_shared' => $data['is_shared'] ?? false, 'alt_text' => $data['alt_text'] ?? null])->id;
                } catch (MediaRejected $e) {
                    throw ValidationException::withMessages(['file' => $e->getMessage()]);
                }
            })
            ->createOptionModalHeading(__('platform::media.upload'));
    }

    /** Pictures only: the list and the upload accept images. */
    public function image(bool $condition = true): static
    {
        $this->onlyImages = $condition;

        // FR-GEN-61: pictures need a description for screen readers and search engines
        $this->live()
            ->hint(fn (MediaPicker $component): ?string => $component->onlyImages && $component->withoutAltText() !== []
                ? __('platform::media.no_alt', ['count' => count($component->withoutAltText())])
                : null)
            ->hintIcon(fn (MediaPicker $component): ?string => $component->onlyImages && $component->withoutAltText() !== [] ? 'heroicon-o-exclamation-triangle' : null)
            ->hintColor('warning');

        return $this;
    }

    /** Keep the chosen ids in the form data (Settings), instead of saving usage on a record. */
    public function storeIds(bool $condition = true): static
    {
        $this->storesIds = $condition;

        return $this;
    }

    /** @return list<int> chosen pictures without alt text in the default language */
    public function withoutAltText(): array
    {
        $ids = array_filter(array_map('intval', (array) $this->getState()));

        return $ids === [] ? [] : MediaItem::query()->whereKey($ids)->get()
            ->filter(fn (MediaItem $item): bool => blank($item->getTranslation('alt_text', app(Languages::class)->default(), false)))
            ->pluck('id')->all();
    }

    public function query(): Builder
    {
        return MediaItem::query()
            ->visibleTo(auth()->user())
            ->where('is_active', true)
            ->when($this->onlyImages, fn (Builder $q) => $q->where('kind', 'image'))
            ->with('media');
    }

    /** @return array<int, string> id => thumbnail + title (HTML) */
    public function labels($items): array
    {
        return collect($items)->mapWithKeys(function (MediaItem $item): array {
            $thumb = $item->previewUrl();
            $badge = $thumb
                ? '<img src="'.e($thumb).'" alt="" style="width:2rem;height:2rem;object-fit:cover;border-radius:.25rem;display:inline-block;vertical-align:middle;margin-inline-end:.5rem">'
                : '<span style="display:inline-block;min-width:2rem;margin-inline-end:.5rem;font-size:.7rem;font-weight:600;opacity:.7">'.e(strtoupper(pathinfo((string) $item->fileName(), PATHINFO_EXTENSION))).'</span>';

            return [$item->id => $badge.e($item->title ?: $item->fileName())];
        })->all();
    }
}
