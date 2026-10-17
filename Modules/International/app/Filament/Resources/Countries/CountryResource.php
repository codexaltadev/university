<?php

namespace Modules\International\Filament\Resources\Countries;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\International\Filament\Resources\Countries\Pages\ManageCountries;
use Modules\International\Models\Country;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use UnitEnum;

/** International › Countries (FR-INTL-01): master data — name per language, ISO code, flag. */
class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static ?string $slug = 'international-countries';

    protected static string|UnitEnum|null $navigationGroup = 'International';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?int $navigationSort = 9;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('international::international.country');
    }

    public static function getPluralModelLabel(): string
    {
        return __('international::international.countries');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TranslatableTabs::make()->columnSpanFull()->schema(fn (string $locale, bool $isDefault): array => [
                TextInput::make("name.{$locale}")->label(__('international::international.fields.country'))->required($isDefault)->maxLength(100),
            ]),
            Grid::make(2)->columnSpanFull()->schema([
                TextInput::make('iso2')->label(__('international::international.fields.iso2'))->required()->length(2)->alpha()->helperText(__('international::international.help.iso2')),
                Toggle::make('is_active')->label(__('platform::relations.active'))->default(true)->inline(false),
            ]),
            MediaPicker::make('flag')->label(__('international::international.fields.flag'))->image()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('partners'))
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('iso2')->label(__('international::international.fields.iso2'))->formatStateUsing(fn (Country $record): string => $record->emoji().' '.$record->iso2),
                TextColumn::make('name')->label(__('international::international.fields.country'))->searchable(),
                TextColumn::make('partners_count')->label(__('international::international.partners')),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
            ])
            ->filters([TrashedFilter::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()->hidden(fn (Country $record): bool => $record->partners_count > 0), RestoreAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCountries::route('/')];
    }
}
