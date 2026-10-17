<?php

namespace Modules\Facilities\Filament\Resources\Facilities;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
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
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Facilities\Filament\Resources\Facilities\Pages\CreateFacility;
use Modules\Facilities\Filament\Resources\Facilities\Pages\EditFacility;
use Modules\Facilities\Filament\Resources\Facilities\Pages\ListFacilities;
use Modules\Facilities\Models\Facility;
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
 * Services › Facilities (M-33, FR-FCL-01..05): title, summary, text per language; icon, picture and
 * gallery from the Media Library; an outside address when there is no text; right: active, Show in,
 * highlight, category, address, SEO. Dragged order.
 */
class FacilityResource extends Resource
{
    protected static ?string $model = Facility::class;

    protected static string|UnitEnum|null $navigationGroup = 'Services';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('facilities::facilities.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('facilities::facilities.plural');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Grid::make(1)->columnSpan(2)->schema([
                    Section::make()->schema([
                        TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                            TextInput::make("title.{$locale}")->label(__('facilities::facilities.fields.title'))->required($isDefault)->maxLength(200),
                            Textarea::make("summary.{$locale}")->label(__('facilities::facilities.fields.summary'))->rows(2)->maxLength(500),
                            RichEditor::make("body.{$locale}")->label(__('facilities::facilities.fields.body'))->helperText(__('facilities::facilities.help.body'))->fileAttachments(false),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('external_url')->label(__('facilities::facilities.fields.external_url'))->regex('#^(https?://\S+|/\S*)$#i')->maxLength(2048),
                            Toggle::make('open_in_new_tab')->label(__('facilities::facilities.fields.open_in_new_tab'))->inline(false),
                        ]),
                    ]),
                    Section::make(__('facilities::facilities.sections.media'))->compact()->collapsible()->schema([
                        MediaPicker::make('icon')->label(__('facilities::facilities.fields.icon'))->image(),
                        MediaPicker::make('image')->label(__('facilities::facilities.fields.image'))->image(),
                        MediaPicker::make('gallery')->label(__('facilities::facilities.fields.gallery'))->image()->multiple(),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    PublishingPanel::make(),
                    Section::make()->compact()->schema([WebAddressField::make(prefix: '/'.Facility::PATH.'/')]),
                    RelatedToField::make(),
                    HighlightPanel::make(),
                    Section::make()->compact()->schema([TermSelect::make('category')->vocabulary(Facility::CATEGORY)]),
                    SeoPanel::make(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['contentRelations', 'terms']))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('icon')->label('')->imageSize(32)->state(fn (Facility $record): ?string => $record->firstMedia('icon')?->url('thumb') ?? $record->firstMedia('image')?->url('thumb')),
                TextColumn::make('title')->label(__('facilities::facilities.fields.title'))->searchable()->wrap(),
                TextColumn::make('shown_in')->label(__('platform::relations.show_in'))
                    ->state(fn (Facility $record): array => ContentFilters::placesOf($record))->badge()->color('gray')->limitList(2)->toggleable(),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
            ])
            ->filters([ContentFilters::shownIn(), ContentFilters::term('category', Facility::CATEGORY), ContentFilters::mainSite(), TrashedFilter::make()])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }

    public static function getRelations(): array
    {
        return [ActivityRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFacilities::route('/'),
            'create' => CreateFacility::route('/create'),
            'edit' => EditFacility::route('/{record}/edit'),
        ];
    }
}
