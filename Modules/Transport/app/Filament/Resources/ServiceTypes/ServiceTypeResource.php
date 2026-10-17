<?php

namespace Modules\Transport\Filament\Resources\ServiceTypes;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Transport\Filament\Resources\ServiceTypes\Pages\ManageServiceTypes;
use Modules\Transport\Models\ServiceType;
use Platform\Filament\Components\TranslatableTabs;
use UnitEnum;

/** Transport › Service types (FR-TRN-03): Regular, Final Exam, Mid-Term … — one default; which runs now is in Settings › Transport. */
class ServiceTypeResource extends Resource
{
    protected static ?string $model = ServiceType::class;

    protected static string|UnitEnum|null $navigationGroup = 'Transport';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('transport::transport.service_type');
    }

    public static function getPluralModelLabel(): string
    {
        return __('transport::transport.service_types');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TranslatableTabs::make()->columnSpanFull()->schema(fn (string $locale, bool $isDefault): array => [
                TextInput::make("name.{$locale}")->label(__('transport::transport.fields.name'))->required($isDefault)->maxLength(150),
            ]),
            Grid::make(3)->columnSpanFull()->schema([
                TextInput::make('code')->label(__('transport::transport.fields.code'))->helperText(__('transport::transport.help.code'))->required()->maxLength(50),
                Toggle::make('is_default')->label(__('transport::transport.fields.is_default'))->inline(false),
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
                TextColumn::make('name')->label(__('transport::transport.fields.name')),
                TextColumn::make('code')->label(__('transport::transport.fields.code'))->fontFamily('mono'),
                IconColumn::make('is_default')->label(__('transport::transport.fields.is_default'))->boolean(),
                TextColumn::make('running')->label('')->badge()->color('success')
                    ->state(fn (ServiceType $record): ?string => ServiceType::running()?->is($record) ? __('transport::transport.running') : null),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageServiceTypes::route('/')];
    }
}
