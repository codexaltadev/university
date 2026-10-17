<?php

namespace Modules\Transport\Filament\Resources\Stops;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
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
use Modules\Transport\Filament\Resources\Stops\Pages\ManageStops;
use Modules\Transport\Models\Stop;
use Platform\Filament\Components\TranslatableTabs;
use UnitEnum;

/** Transport › Stops (FR-TRN-01): name and landmark per language, latitude / longitude (open in Google Maps to check). */
class StopResource extends Resource
{
    protected static ?string $model = Stop::class;

    protected static string|UnitEnum|null $navigationGroup = 'Transport';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('transport::transport.stop');
    }

    public static function getPluralModelLabel(): string
    {
        return __('transport::transport.stops_title');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TranslatableTabs::make()->columnSpanFull()->schema(fn (string $locale, bool $isDefault): array => [
                TextInput::make("name.{$locale}")->label(__('transport::transport.fields.name'))->required($isDefault)->maxLength(150),
                TextInput::make("landmark.{$locale}")->label(__('transport::transport.fields.landmark'))->maxLength(200),
            ]),
            Grid::make(3)->columnSpanFull()->schema([
                TextInput::make('latitude')->label(__('transport::transport.fields.latitude'))->numeric()->minValue(-90)->maxValue(90)->helperText(__('transport::transport.help.position')),
                TextInput::make('longitude')->label(__('transport::transport.fields.longitude'))->numeric()->minValue(-180)->maxValue(180),
                Toggle::make('is_active')->label(__('platform::relations.active'))->default(true)->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label(__('transport::transport.fields.name'))->searchable()
                    ->description(fn (Stop $record): ?string => $record->landmark ?: null),
                TextColumn::make('position')->label(__('transport::transport.fields.position'))
                    ->state(fn (Stop $record): ?string => $record->latitude !== null ? $record->latitude.', '.$record->longitude : null)->placeholder('—')
                    ->url(fn (Stop $record): ?string => $record->mapUrl(), shouldOpenInNewTab: true),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
            ])
            ->filters([TrashedFilter::make()])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageStops::route('/')];
    }
}
