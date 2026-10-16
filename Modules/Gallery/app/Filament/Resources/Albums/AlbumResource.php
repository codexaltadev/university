<?php

namespace Modules\Gallery\Filament\Resources\Albums;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Gallery\Filament\Components\AlbumItems;
use Modules\Gallery\Filament\Resources\Albums\Pages\CreateAlbum;
use Modules\Gallery\Filament\Resources\Albums\Pages\EditAlbum;
use Modules\Gallery\Filament\Resources\Albums\Pages\ListAlbums;
use Modules\Gallery\Filament\Resources\Albums\RelationManagers\VideosRelationManager;
use Modules\Gallery\Models\Album;
use Platform\Filament\Components\HighlightPanel;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\PublishingPanel;
use Platform\Filament\Components\RelatedToField;
use Platform\Filament\Components\SeoPanel;
use Platform\Filament\Components\TermSelect;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\Components\WebAddressField;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use Platform\Filament\Tables\ContentFilters;
use UnitEnum;

/**
 * Content › Gallery (M-22, FR-GAL-01..06): title and description per language, the pictures and
 * uploaded videos from the Media Library (AlbumItems), cover, date, series, event; link videos
 * below (Videos tab); right: publishing, address, Show in, highlight, category, SEO.
 */
class AlbumResource extends Resource
{
    protected static ?string $model = Album::class;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?int $navigationSort = 15;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('gallery::gallery.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('gallery::gallery.plural');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /** @return array<string, string> */
    public static function seriesOptions(): array
    {
        return collect(Album::SERIES)->mapWithKeys(fn (string $s): array => [$s => __("gallery::gallery.series.{$s}")])->all();
    }

    public static function form(Schema $schema): Schema
    {
        $event = 'Modules\\Events\\Models\\Event';

        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Grid::make(1)->columnSpan(2)->schema([
                    Section::make()->schema([
                        TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                            TextInput::make("title.{$locale}")->label(__('gallery::gallery.fields.title'))->required($isDefault)->maxLength(255),
                            Textarea::make("description.{$locale}")->label(__('gallery::gallery.fields.description'))->rows(3)->maxLength(2000),
                        ]),
                        Grid::make(3)->schema([
                            DatePicker::make('occurred_on')->label(__('gallery::gallery.fields.occurred_on')),
                            Select::make('series_type')->label(__('gallery::gallery.fields.series_type'))->options(self::seriesOptions())
                                ->placeholder(__('gallery::gallery.series.none')),
                            Select::make('event_id')->label(__('gallery::gallery.fields.event'))->searchable()
                                ->visible(fn (): bool => class_exists($event))
                                ->getSearchResultsUsing(fn (string $search): array => $event::query()->where('title', 'like', "%{$search}%")->latest('starts_at')->limit(20)->get()
                                    ->mapWithKeys(fn ($e): array => [$e->id => $e->title.' — '.$e->starts_at?->format('d M Y')])->all())
                                ->getOptionLabelUsing(fn ($value): ?string => $event::query()->find($value)?->title),
                        ]),
                    ]),
                    Section::make(__('gallery::gallery.fields.items'))->compact()->schema([AlbumItems::make()->hiddenLabel()]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    PublishingPanel::make(),
                    Section::make()->compact()->schema([
                        MediaPicker::make('cover')->label(__('gallery::gallery.fields.cover'))->helperText(__('gallery::gallery.help.cover'))->image(),
                        WebAddressField::make(prefix: '/'.Album::PATH.'/'),
                    ]),
                    RelatedToField::make(),
                    HighlightPanel::make(),
                    Section::make()->compact()->schema([TermSelect::make('category')->vocabulary(Album::CATEGORY)]),
                    SeoPanel::make(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['contentRelations', 'terms', 'videos'])
                ->withCount(['mediaUsages as items_count' => fn (Builder $u) => $u->where('field', Album::ITEMS)]))
            ->defaultSort(fn (Builder $query) => $query->newest())
            ->columns([
                ImageColumn::make('cover')->label('')->imageSize(44)->state(fn (Album $record): ?string => $record->coverUrl('thumb')),
                TextColumn::make('title')->label(__('gallery::gallery.fields.title'))->searchable()->wrap()->limit(80)
                    ->description(fn (Album $record): ?string => $record->series_type ? __("gallery::gallery.series.{$record->series_type}") : null),
                TextColumn::make('items_count')->label(__('gallery::gallery.fields.items'))->numeric()
                    ->formatStateUsing(fn (Album $record): string => trans_choice('gallery::gallery.counts', $record->items_count + $record->videos->count(), ['count' => $record->items_count + $record->videos->count()])),
                TextColumn::make('occurred_on')->label(__('gallery::gallery.fields.occurred_on'))->date('d M Y')->sortable()->toggleable(),
                TextColumn::make('shown_in')->label(__('platform::relations.show_in'))
                    ->state(fn (Album $record): array => ContentFilters::placesOf($record))->badge()->color('gray')->limitList(2)->toggleable(),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
            ])
            ->filters([
                SelectFilter::make('series_type')->label(__('gallery::gallery.fields.series_type'))->options(self::seriesOptions()),
                ContentFilters::shownIn(),
                ContentFilters::term('category', Album::CATEGORY),
                ContentFilters::mainSite(),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }

    public static function getRelations(): array
    {
        return [VideosRelationManager::class, ActivityRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAlbums::route('/'),
            'create' => CreateAlbum::route('/create'),
            'edit' => EditAlbum::route('/{record}/edit'),
        ];
    }
}
