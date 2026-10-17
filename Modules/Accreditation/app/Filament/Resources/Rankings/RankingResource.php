<?php

namespace Modules\Accreditation\Filament\Resources\Rankings;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
use Modules\Accreditation\Filament\Resources\Rankings\Pages\CreateRanking;
use Modules\Accreditation\Filament\Resources\Rankings\Pages\EditRanking;
use Modules\Accreditation\Filament\Resources\Rankings\Pages\ListRankings;
use Modules\Accreditation\Models\Ranking;
use Platform\Filament\Components\HighlightPanel;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\PublishingPanel;
use Platform\Filament\Components\RelatedToField;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use Platform\Filament\Tables\ContentFilters;
use UnitEnum;

/**
 * Services › Rankings (M-34, FR-ACR-03/04): agency (its key groups the years), year, overall /
 * subject / region, the rank; logo and badge from the Media Library. "Next year" copies one.
 */
class RankingResource extends Resource
{
    protected static ?string $model = Ranking::class;

    protected static string|UnitEnum|null $navigationGroup = 'Services';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?int $navigationSort = 9;

    protected static ?string $recordTitleAttribute = 'agency_name';

    public static function getModelLabel(): string
    {
        return __('accreditation::accreditation.ranking');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accreditation::accreditation.rankings');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /** @return array<string, string> */
    public static function agencyOptions(): array
    {
        return Ranking::query()->withoutGlobalScopes()->whereNull('deleted_at')->newest()->get()->unique('agency_key')
            ->mapWithKeys(fn (Ranking $r): array => [$r->agency_key => (string) $r->agency_name])->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Grid::make(1)->columnSpan(2)->schema([
                    Section::make()->schema([
                        TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                            TextInput::make("agency_name.{$locale}")->label(__('accreditation::accreditation.fields.agency_name'))->required($isDefault)->maxLength(200),
                            TextInput::make("subject.{$locale}")->label(__('accreditation::accreditation.fields.subject'))->maxLength(200)->visible(fn (Get $get): bool => $get('scope') !== 'overall'),
                            Textarea::make("summary.{$locale}")->label(__('accreditation::accreditation.fields.summary'))->rows(2)->maxLength(600),
                            RichEditor::make("body.{$locale}")->label(__('accreditation::accreditation.fields.body'))->fileAttachments(false),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('agency_key')->label(__('accreditation::accreditation.fields.agency_key'))->helperText(__('accreditation::accreditation.help.agency_key'))
                                ->datalist(fn (): array => array_keys(self::agencyOptions()))->alphaDash()->maxLength(50),
                            Select::make('scope')->label(__('accreditation::accreditation.fields.scope'))->native(false)->live()->default('overall')->required()
                                ->options(collect(Ranking::SCOPES)->mapWithKeys(fn (string $s): array => [$s => __('accreditation::accreditation.scopes.'.$s)])->all()),
                            TextInput::make('year')->label(__('accreditation::accreditation.fields.year'))->numeric()->required()->minValue(1900)->maxValue((int) now()->year + 1)->default((int) now()->year),
                            TextInput::make('rank_label')->label(__('accreditation::accreditation.fields.rank_label'))->placeholder('801–1000')->required()->maxLength(30),
                            TextInput::make('rank_value')->label(__('accreditation::accreditation.fields.rank_value'))->helperText(__('accreditation::accreditation.help.rank_value'))->numeric()->minValue(1),
                            TextInput::make('ranking_url')->label(__('accreditation::accreditation.fields.ranking_url'))->url()->maxLength(2048),
                        ]),
                    ]),
                    Section::make(__('accreditation::accreditation.sections.media'))->compact()->collapsible()->schema([
                        MediaPicker::make('logo')->label(__('accreditation::accreditation.fields.logo'))->image(),
                        MediaPicker::make('badge')->label(__('accreditation::accreditation.fields.badge'))->image(),
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
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['contentRelations']))
            ->defaultSort(fn (Builder $query) => $query->newest())
            ->columns([
                ImageColumn::make('logo')->label('')->imageSize(36)->state(fn (Ranking $record): ?string => ($record->firstMedia('badge') ?? $record->firstMedia('logo'))?->url('thumb')),
                TextColumn::make('agency_name')->label(__('accreditation::accreditation.fields.agency_name'))->searchable()->wrap()->description(fn (Ranking $record): ?string => $record->scope !== 'overall' ? $record->subject : null),
                TextColumn::make('year')->label(__('accreditation::accreditation.fields.year'))->sortable(),
                TextColumn::make('rank_label')->label(__('accreditation::accreditation.fields.rank_label'))->badge(),
                TextColumn::make('shown_in')->label(__('platform::relations.show_in'))
                    ->state(fn (Ranking $record): array => ContentFilters::placesOf($record))->badge()->color('gray')->limitList(2)->toggleable(),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
            ])
            ->filters([
                SelectFilter::make('agency_key')->label(__('accreditation::accreditation.fields.agency_name'))->options(fn (): array => self::agencyOptions()),
                ContentFilters::shownIn(), ContentFilters::mainSite(), TrashedFilter::make(),
            ])
            ->recordActions([
                ReplicateAction::make('next_year')->label(__('accreditation::accreditation.next_year'))->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->excludeAttributes(['active_key', 'rank_value'])
                    ->beforeReplicaSaved(fn (Ranking $replica) => $replica->year = $replica->year + 1)
                    ->successRedirectUrl(fn (Ranking $replica): string => self::getUrl('edit', ['record' => $replica])),
                EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [ActivityRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRankings::route('/'),
            'create' => CreateRanking::route('/create'),
            'edit' => EditRanking::route('/{record}/edit'),
        ];
    }
}
