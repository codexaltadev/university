<?php

namespace Modules\International\Filament\Resources\Visitors;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
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
use Modules\International\Filament\Resources\Institutions\InstitutionResource;
use Modules\International\Filament\Resources\Visitors\Pages\CreateVisitor;
use Modules\International\Filament\Resources\Visitors\Pages\EditVisitor;
use Modules\International\Filament\Resources\Visitors\Pages\ListVisitors;
use Modules\International\Models\Visitor;
use Platform\Filament\Components\HighlightPanel;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\PublishingPanel;
use Platform\Filament\Components\RelatedToField;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use Platform\Filament\Tables\ContentFilters;
use UnitEnum;

/**
 * International › Visitors (M-35, FR-INTL-06): name, designation, organisation per language (free
 * text — guests are not in the directory), country, day, photo; the Gallery album when installed.
 */
class VisitorResource extends Resource
{
    protected static ?string $model = Visitor::class;

    protected static ?string $slug = 'international-visitors';

    protected static string|UnitEnum|null $navigationGroup = 'International';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('international::international.visitor');
    }

    public static function getPluralModelLabel(): string
    {
        return __('international::international.visitors');
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
                            TextInput::make("name.{$locale}")->label(__('international::international.fields.visitor_name'))->required($isDefault)->maxLength(250),
                            TextInput::make("designation.{$locale}")->label(__('international::international.fields.designation'))->maxLength(250),
                            TextInput::make("organization.{$locale}")->label(__('international::international.fields.organization'))->maxLength(250),
                            RichEditor::make("body.{$locale}")->label(__('international::international.fields.body'))->fileAttachments(false),
                        ]),
                        Grid::make(3)->schema([
                            Select::make('country_id')->label(__('international::international.fields.country'))->options(fn (): array => InstitutionResource::countryOptions())->searchable(),
                            DatePicker::make('visited_on')->label(__('international::international.fields.visited_on'))->required()->default(today()),
                            Toggle::make('is_external')->label(__('international::international.fields.is_external'))->default(true)->inline(false),
                        ]),
                        Select::make('album_id')->label(__('international::international.fields.album'))->searchable()
                            ->visible(fn (): bool => class_exists(Visitor::ALBUM))
                            ->options(fn (): array => class_exists(Visitor::ALBUM) ? (Visitor::ALBUM)::query()->latest('id')->limit(300)->get()->mapWithKeys(fn ($a): array => [$a->getKey() => (string) $a->title])->all() : []),
                        MediaPicker::make('photo')->label(__('international::international.fields.photo'))->image(),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    PublishingPanel::make(),
                    RelatedToField::make(),
                    HighlightPanel::make(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['contentRelations', 'country']))
            ->defaultSort(fn (Builder $query) => $query->newest())
            ->columns([
                ImageColumn::make('photo')->label('')->imageSize(36)->circular()->state(fn (Visitor $record): ?string => $record->firstMedia('photo')?->url('thumb')),
                TextColumn::make('name')->label(__('international::international.fields.visitor_name'))->searchable()->wrap()->description(fn (Visitor $record): ?string => $record->organization),
                TextColumn::make('country.name')->label(__('international::international.fields.country')),
                TextColumn::make('visited_on')->label(__('international::international.fields.visited_on'))->date()->sortable(),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
            ])
            ->filters([
                SelectFilter::make('country_id')->label(__('international::international.fields.country'))->options(fn (): array => InstitutionResource::countryOptions())->searchable(),
                ContentFilters::shownIn(), ContentFilters::mainSite(), TrashedFilter::make(),
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
            'index' => ListVisitors::route('/'),
            'create' => CreateVisitor::route('/create'),
            'edit' => EditVisitor::route('/{record}/edit'),
        ];
    }
}
