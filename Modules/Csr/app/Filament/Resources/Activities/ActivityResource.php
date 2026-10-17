<?php

namespace Modules\Csr\Filament\Resources\Activities;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
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
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Csr\Filament\Resources\Activities\Pages\CreateActivity;
use Modules\Csr\Filament\Resources\Activities\Pages\EditActivity;
use Modules\Csr\Filament\Resources\Activities\Pages\ListActivities;
use Modules\Csr\Models\Activity;
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
 * Services › CSR (M-36, FR-CSR-01..04): title, summary, text per language; date, video, outside
 * link; picture and gallery from the Media Library; right: active, Show in, highlight, category, address, SEO.
 */
class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|UnitEnum|null $navigationGroup = 'Services';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHandRaised;

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('csr::csr.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('csr::csr.plural');
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
                            TextInput::make("title.{$locale}")->label(__('csr::csr.fields.title'))->required($isDefault)->maxLength(250),
                            Textarea::make("summary.{$locale}")->label(__('csr::csr.fields.summary'))->rows(2)->maxLength(600),
                            RichEditor::make("body.{$locale}")->label(__('csr::csr.fields.body'))->fileAttachments(false),
                        ]),
                        Grid::make(3)->schema([
                            DatePicker::make('occurred_on')->label(__('csr::csr.fields.occurred_on')),
                            TextInput::make('video_url')->label(__('csr::csr.fields.video_url'))->url()->maxLength(2048),
                            TextInput::make('external_url')->label(__('csr::csr.fields.external_url'))->url()->maxLength(2048),
                        ]),
                    ]),
                    Section::make(__('csr::csr.sections.media'))->compact()->collapsible()->schema([
                        MediaPicker::make('image')->label(__('csr::csr.fields.image'))->image(),
                        MediaPicker::make('gallery')->label(__('csr::csr.fields.gallery'))->image()->multiple(),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    PublishingPanel::make(),
                    Section::make()->compact()->schema([WebAddressField::make(prefix: '/'.Activity::PATH.'/')]),
                    RelatedToField::make(),
                    HighlightPanel::make(),
                    Section::make()->compact()->schema([TermSelect::make('category')->vocabulary(Activity::CATEGORY)]),
                    SeoPanel::make(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['contentRelations', 'terms']))
            ->defaultSort(fn (Builder $query) => $query->newest())
            ->columns([
                ImageColumn::make('image')->label('')->imageSize(40)->state(fn (Activity $record): ?string => $record->firstMedia('image')?->url('thumb')),
                TextColumn::make('title')->label(__('csr::csr.fields.title'))->searchable()->wrap(),
                TextColumn::make('occurred_on')->label(__('csr::csr.fields.occurred_on'))->date()->sortable(),
                TextColumn::make('category')->label(__('csr::csr.fields.category'))->state(fn (Activity $record): ?string => $record->termsIn(Activity::CATEGORY)->first()?->name)->toggleable(),
                TextColumn::make('shown_in')->label(__('platform::relations.show_in'))
                    ->state(fn (Activity $record): array => ContentFilters::placesOf($record))->badge()->color('gray')->limitList(2)->toggleable(),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
            ])
            ->filters([ContentFilters::shownIn(), ContentFilters::term('category', Activity::CATEGORY), ContentFilters::mainSite(), TrashedFilter::make()])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }

    public static function getRelations(): array
    {
        return [ActivityRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
            'create' => CreateActivity::route('/create'),
            'edit' => EditActivity::route('/{record}/edit'),
        ];
    }
}
