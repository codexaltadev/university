<?php

namespace Platform\Filament\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Illuminate\Database\Eloquent\Model;
use Platform\Media\MediaUsage;

/**
 * Files with their own titles (FR-NOT-01, FR-DOC-…): each row is a file from the Media Library
 * and its title per language for this place ("Exam routine — Fall 2026"), in order. Saved as the
 * media uses of $field with meta.title, so the same file can carry another title elsewhere.
 *
 *     AttachmentList::make('attachments')   // in the form of a model with HasMediaUsage
 *     $record->mediaUsagesIn('attachments') // on the website: ->item, ->meta['title'][$locale]
 */
class AttachmentList
{
    public static function make(string $field = 'attachments'): Repeater
    {
        return Repeater::make($field)
            ->label(__('platform::media.attachments'))
            ->schema([
                MediaPicker::make('media')->label(__('platform::media.file'))->storeIds()->required(),
                Grid::make(2)->schema(TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                    TextInput::make("title.{$locale}")->label(__('platform::media.attachment_title')." ({$language})")
                        ->helperText($isDefault ? __('platform::media.attachment_title_help') : null)->maxLength(255),
                ])),
            ])
            ->itemLabel(fn (array $state): ?string => collect((array) ($state['title'] ?? []))->filter()->first())
            ->reorderable()->collapsible()->defaultItems(0)
            ->addActionLabel(__('platform::media.add_attachment'))
            ->dehydrated(false)
            ->afterStateHydrated(function (Repeater $component, ?Model $record) use ($field): void {
                if ($record?->exists && method_exists($record, 'mediaUsagesIn')) {
                    $component->state($record->mediaUsagesIn($field)->map(fn (MediaUsage $u): array => [
                        'media' => $u->media_item_id,
                        'title' => (array) ($u->meta['title'] ?? []),
                    ])->all());
                }
            })
            ->saveRelationshipsUsing(function (Model $record, ?array $state) use ($field): void {
                $rows = array_values(array_filter((array) $state, fn (array $row): bool => filled($row['media'] ?? null)));
                $meta = [];
                foreach ($rows as $row) {
                    $title = array_filter((array) ($row['title'] ?? []), fn ($t): bool => filled($t));
                    $meta[(int) $row['media']] = $title !== [] ? ['title' => $title] : [];
                }
                $record->syncMedia($field, array_map(fn (array $row): int => (int) $row['media'], $rows), $meta);
            });
    }
}
