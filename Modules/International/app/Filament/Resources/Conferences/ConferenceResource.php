<?php

namespace Modules\International\Filament\Resources\Conferences;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\International\Filament\Resources\Conferences\Pages\CreateConference;
use Modules\International\Filament\Resources\Conferences\Pages\EditConference;
use Modules\International\Filament\Resources\Conferences\Pages\ListConferences;
use Modules\International\Models\Conference;
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

/** International › Conferences (M-35, FR-INTL-05): title, venue, organiser, text per language; dates, website, picture; category. */
class ConferenceResource extends Resource
{
    protected static ?string $model = Conference::class;

    protected static ?string $slug = 'international-conferences';

    protected static string|UnitEnum|null $navigationGroup = 'International';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('international::international.conference');
    }

    public static function getPluralModelLabel(): string
    {
        return __('international::international.conferences');
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
                            TextInput::make("title.{$locale}")->label(__('international::international.fields.title'))->required($isDefault)->maxLength(250),
                            TextInput::make("venue.{$locale}")->label(__('international::international.fields.venue'))->maxLength(250),
                            TextInput::make("organizer_label.{$locale}")->label(__('international::international.fields.organizer_label'))->maxLength(250),
                            RichEditor::make("body.{$locale}")->label(__('international::international.fields.body'))->fileAttachments(false),
                        ]),
                        Grid::make(3)->schema([
                            DatePicker::make('starts_on')->label(__('international::international.fields.starts_on')),
                            DatePicker::make('ends_on')->label(__('international::international.fields.ends_on'))->afterOrEqual('starts_on'),
                            TextInput::make('website_url')->label(__('international::international.fields.website_url'))->url()->maxLength(2048),
                        ]),
                        MediaPicker::make('image')->label(__('international::international.fields.image'))->image(),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    PublishingPanel::make(),
                    Section::make()->compact()->schema([WebAddressField::make(prefix: '/'.Conference::PATH.'/')]),
                    RelatedToField::make(),
                    HighlightPanel::make(),
                    Section::make()->compact()->schema([TermSelect::make('category')->vocabulary(Conference::CATEGORY)]),
                    SeoPanel::make(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['contentRelations']))
            ->defaultSort(fn (Builder $query) => $query->newest())
            ->columns([
                TextColumn::make('title')->label(__('international::international.fields.title'))->searchable()->wrap(),
                TextColumn::make('starts_on')->label(__('international::international.fields.starts_on'))->date()->sortable(),
                TextColumn::make('venue')->label(__('international::international.fields.venue'))->toggleable(),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
            ])
            ->filters([ContentFilters::shownIn(), ContentFilters::term('category', Conference::CATEGORY), ContentFilters::mainSite(), TrashedFilter::make()])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }

    public static function getRelations(): array
    {
        return [ActivityRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConferences::route('/'),
            'create' => CreateConference::route('/create'),
            'edit' => EditConference::route('/{record}/edit'),
        ];
    }
}
