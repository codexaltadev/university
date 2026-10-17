<?php

namespace Modules\International\Filament\Resources\Institutions;

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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\International\Filament\Resources\Institutions\Pages\CreateInstitution;
use Modules\International\Filament\Resources\Institutions\Pages\EditInstitution;
use Modules\International\Filament\Resources\Institutions\Pages\ListInstitutions;
use Modules\International\Models\Country;
use Modules\International\Models\Institution;
use Platform\Filament\Components\HighlightPanel;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\PublishingPanel;
use Platform\Filament\Components\RelatedToField;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use Platform\Filament\Tables\ContentFilters;
use UnitEnum;

/**
 * International › Partner institutions (M-35, FR-INTL-01..04, 07): name per language, country,
 * agreement, signed / expires, website, logo from the Media Library; right: active, Show in, featured
 * (Highlight). Excel / CSV import on the list.
 */
class InstitutionResource extends Resource
{
    protected static ?string $model = Institution::class;

    protected static ?string $slug = 'international-partners';

    protected static string|UnitEnum|null $navigationGroup = 'International';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('international::international.partner');
    }

    public static function getPluralModelLabel(): string
    {
        return __('international::international.partners');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /** @return array<string, string> */
    public static function agreementOptions(): array
    {
        return collect(Institution::AGREEMENTS)->mapWithKeys(fn (string $a): array => [$a => __('international::international.agreements.'.$a)])->all();
    }

    /** @return array<int, string> */
    public static function countryOptions(): array
    {
        return Country::query()->where('is_active', true)->get()->sortBy(fn (Country $c): string => (string) $c->name)->mapWithKeys(fn (Country $c): array => [$c->id => (string) $c->name])->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Grid::make(1)->columnSpan(2)->schema([
                    Section::make()->schema([
                        TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                            TextInput::make("name.{$locale}")->label(__('international::international.fields.name'))->required($isDefault)->maxLength(250),
                            Textarea::make("description.{$locale}")->label(__('international::international.fields.description'))->rows(3)->maxLength(2000),
                        ]),
                        Grid::make(2)->schema([
                            Select::make('country_id')->label(__('international::international.fields.country'))->options(fn (): array => self::countryOptions())->searchable()->required(),
                            Select::make('agreement_type')->label(__('international::international.fields.agreement_type'))->options(self::agreementOptions())->default('mou')->required()->native(false),
                            DatePicker::make('signed_on')->label(__('international::international.fields.signed_on')),
                            DatePicker::make('expires_on')->label(__('international::international.fields.expires_on'))->afterOrEqual('signed_on')->helperText(__('international::international.help.expires_on')),
                        ]),
                        TextInput::make('website_url')->label(__('international::international.fields.website_url'))->url()->maxLength(2048),
                        MediaPicker::make('logo')->label(__('international::international.fields.logo'))->image(),
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
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('logo')->label('')->imageSize(32)->state(fn (Institution $record): ?string => $record->firstMedia('logo')?->url('thumb')),
                TextColumn::make('name')->label(__('international::international.fields.name'))->searchable()->wrap(),
                TextColumn::make('country.name')->label(__('international::international.fields.country'))->sortable(false),
                TextColumn::make('agreement_type')->label(__('international::international.fields.agreement_type'))->badge()->formatStateUsing(fn (string $state): string => self::agreementOptions()[$state] ?? $state),
                TextColumn::make('expires_on')->label(__('international::international.fields.expires_on'))->date()->sortable()->toggleable()
                    ->color(fn (Institution $record): ?string => $record->isExpired() ? 'danger' : null),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
            ])
            ->filters([
                SelectFilter::make('country_id')->label(__('international::international.fields.country'))->options(fn (): array => self::countryOptions())->searchable(),
                SelectFilter::make('agreement_type')->label(__('international::international.fields.agreement_type'))->options(self::agreementOptions()),
                TernaryFilter::make('expired')->label(__('international::international.expired'))
                    ->queries(true: fn (Builder $q) => $q->whereDate('expires_on', '<', today()->toDateString()), false: fn (Builder $q) => $q->notExpired()),
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
            'index' => ListInstitutions::route('/'),
            'create' => CreateInstitution::route('/create'),
            'edit' => EditInstitution::route('/{record}/edit'),
        ];
    }
}
