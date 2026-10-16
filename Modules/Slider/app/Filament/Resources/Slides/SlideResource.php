<?php

namespace Modules\Slider\Filament\Resources\Slides;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
use Modules\Slider\Filament\Resources\Slides\Pages\CreateSlide;
use Modules\Slider\Filament\Resources\Slides\Pages\EditSlide;
use Modules\Slider\Filament\Resources\Slides\Pages\ListSlides;
use Modules\Slider\Models\Slide;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\PublishingPanel;
use Platform\Filament\Components\RelatedToField;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use Platform\Filament\Tables\ContentFilters;
use UnitEnum;

/**
 * Content › Slider (M-23, FR-SLD-01..07): hero slide or page banner; title, subtitle, up to four
 * bullets per language, up to two buttons; desktop and mobile picture and a background video from
 * the Media Library; text position, overlay, video on phones; right: active, from / until,
 * Show in (many places at once). Order is dragged.
 */
class SlideResource extends Resource
{
    protected static ?string $model = Slide::class;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static ?int $navigationSort = 16;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('slider::slider.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('slider::slider.plural');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /** @return array<string, string> */
    public static function placementOptions(): array
    {
        return collect(Slide::PLACEMENTS)->mapWithKeys(fn (string $p): array => [$p => __("slider::slider.placements.{$p}")])->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Grid::make(1)->columnSpan(2)->schema([
                    Section::make()->schema([
                        Select::make('placement')->label(__('slider::slider.fields.placement'))->options(self::placementOptions())
                            ->default('hero')->selectablePlaceholder(false)->helperText(__('slider::slider.help.placement'))->required(),
                        TranslatableTabs::make()->schema(fn (string $locale): array => [
                            TextInput::make("title.{$locale}")->label(__('slider::slider.fields.title'))->maxLength(150),
                            TextInput::make("subtitle.{$locale}")->label(__('slider::slider.fields.subtitle'))->maxLength(255),
                            TagsInput::make("bullets.{$locale}")->label(__('slider::slider.fields.bullets'))->helperText(__('slider::slider.help.bullets'))
                                ->reorderable()->rules(['array', 'max:'.Slide::MAX_BULLETS]),
                        ]),
                        Repeater::make('buttons')->label(__('slider::slider.fields.buttons'))->maxItems(Slide::MAX_BUTTONS)->defaultItems(0)
                            ->schema([
                                Grid::make(2)->schema(TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                                    TextInput::make("label.{$locale}")->label(__('slider::slider.fields.button_label')." ({$language})")->required($isDefault)->maxLength(60),
                                ])),
                                TextInput::make('url')->label(__('slider::slider.fields.button_url'))->regex('#^(https?://|/)#')->required()->maxLength(1000),
                            ])
                            ->addActionLabel(__('slider::slider.add_button'))->reorderable()->collapsible(),
                    ]),
                    Section::make(__('slider::slider.sections.media'))->compact()->schema([
                        MediaPicker::make('image_desktop')->label(__('slider::slider.fields.image_desktop'))->helperText(__('slider::slider.help.image_desktop'))->image()->required(),
                        MediaPicker::make('image_mobile')->label(__('slider::slider.fields.image_mobile'))->helperText(__('slider::slider.help.image_mobile'))->image(),
                        MediaPicker::make('video')->label(__('slider::slider.fields.video'))->helperText(__('slider::slider.help.video')),
                        Grid::make(3)->schema([
                            Select::make('text_position')->label(__('slider::slider.fields.text_position'))->default('left')->selectablePlaceholder(false)
                                ->options(collect(Slide::POSITIONS)->mapWithKeys(fn (string $p): array => [$p => __("slider::slider.positions.{$p}")])->all()),
                            TextInput::make('overlay_opacity')->label(__('slider::slider.fields.overlay_opacity'))->integer()->minValue(0)->maxValue(80)->default(40)->suffix('%'),
                            Toggle::make('video_on_mobile')->label(__('slider::slider.fields.video_on_mobile'))->helperText(__('slider::slider.help.video_on_mobile'))->inline(false),
                        ]),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    PublishingPanel::make(),
                    RelatedToField::make(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['contentRelations']))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('picture')->label('')->imageWidth(72)->imageHeight(40)
                    ->state(fn (Slide $record): ?string => $record->desktopImage()?->url('thumb')),
                TextColumn::make('title')->label(__('slider::slider.fields.title'))->searchable()->wrap()->limit(70)->placeholder('—')
                    ->description(fn (Slide $record): ?string => $record->videoItem() ? __('slider::slider.with_video') : null),
                TextColumn::make('placement')->label(__('slider::slider.fields.placement'))->badge()
                    ->formatStateUsing(fn (string $state): string => __("slider::slider.placements.{$state}")),
                TextColumn::make('shown_in')->label(__('platform::relations.show_in'))
                    ->state(fn (Slide $record): array => ContentFilters::placesOf($record))->badge()->color('gray')->limitList(3),
                TextColumn::make('expires_at')->label(__('platform::relations.expires_at'))->dateTime('d M Y')->placeholder('—')->toggleable()
                    ->color(fn (Slide $record): ?string => $record->isExpired() ? 'danger' : null),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
            ])
            ->filters([
                SelectFilter::make('placement')->label(__('slider::slider.fields.placement'))->options(self::placementOptions()),
                ContentFilters::shownIn(),
                ContentFilters::mainSite(),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }

    public static function getRelations(): array
    {
        return [ActivityRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSlides::route('/'),
            'create' => CreateSlide::route('/create'),
            'edit' => EditSlide::route('/{record}/edit'),
        ];
    }
}
