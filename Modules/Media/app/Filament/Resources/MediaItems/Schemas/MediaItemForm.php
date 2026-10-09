<?php

namespace Modules\Media\Filament\Resources\MediaItems\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Modules\Media\Filament\Resources\MediaItems\MediaActions;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Media\MediaFolder;
use Platform\Media\MediaItem;

class MediaItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Section::make()->columnSpan(2)->schema([
                    TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                        TextInput::make("title.{$locale}")->label(__('media::media.fields.title'))->required($isDefault)->maxLength(255),
                        TextInput::make("alt_text.{$locale}")->label(__('media::media.fields.alt_text'))
                            ->helperText(__('media::media.help.alt_text'))->maxLength(255),
                        TextInput::make("caption.{$locale}")->label(__('media::media.fields.caption'))->maxLength(500),
                        Textarea::make("description.{$locale}")->label(__('media::media.fields.description'))->rows(3)->maxLength(2000),
                    ]),
                    Select::make('folder_id')->label(__('media::media.fields.folder'))
                        ->options(fn (): array => MediaFolder::options(Auth::user()))->placeholder(__('media::media.no_folder')),
                    Toggle::make('is_shared')->label(__('media::media.fields.shared'))->helperText(__('media::media.help.shared')),
                    Toggle::make('is_active')->label(__('media::media.fields.active')),
                ]),
                Section::make(__('media::media.sections.file'))->columnSpan(1)->schema([
                    ImageEntry::make('preview')->hiddenLabel()
                        ->state(fn (MediaItem $record): ?string => $record->kind === 'image' ? $record->url('medium') : null)
                        ->visible(fn (MediaItem $record): bool => $record->kind === 'image')
                        ->imageWidth('100%')
                        ->imageHeight('220px')
                        ->extraImgAttributes(['style' => 'object-fit: contain; max-width: 100%; border-radius: .5rem; background: rgba(148, 163, 184, .12);']),
                    TextEntry::make('file_name')->label(__('media::media.fields.file'))
                        ->state(fn (MediaItem $record): ?string => $record->fileName()),
                    TextEntry::make('facts')->label(__('media::media.fields.details'))
                        ->state(fn (MediaItem $record): string => implode(' · ', array_filter([
                            __("media::media.kinds.{$record->kind}"),
                            $record->mime_type,
                            Number::fileSize($record->size_bytes, 1),
                            $record->width ? "{$record->width} × {$record->height}px" : null,
                            $record->is_private ? __('media::media.fields.private') : null,
                        ]))),
                    TextEntry::make('uploaded')->label(__('media::media.fields.uploaded'))
                        ->state(fn (MediaItem $record): string => trim(($record->creator?->name ?? '—').' · '.$record->created_at?->format('d M Y H:i'))),
                    TextEntry::make('link')->label(__('media::media.fields.link'))
                        ->state(fn (MediaItem $record): ?string => $record->permalink())
                        ->copyable()
                        ->hidden(fn (MediaItem $record): bool => $record->is_private)
                        ->size('xs'),
                ]),
            ]),
            Section::make(__('media::media.sections.usage'))->columnSpanFull()->schema([
                TextEntry::make('usages')->hiddenLabel()->state(fn (MediaItem $record) => MediaActions::usageHtml($record))->html(),
            ]),
        ]);
    }
}
