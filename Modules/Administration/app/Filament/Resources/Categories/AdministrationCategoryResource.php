<?php

namespace Modules\Administration\Filament\Resources\Categories;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Administration\Filament\Resources\Categories\Pages\ManageAdministrationCategories;
use Modules\Administration\Models\AdministrationCategory;
use Platform\Filament\Components\TranslatableTabs;
use UnitEnum;

/** People › Staff categories (FR-ADMN-02): the ladder from the top, drag to reorder; group and "in the directory". */
class AdministrationCategoryResource extends Resource
{
    protected static ?string $model = AdministrationCategory::class;

    protected static string|UnitEnum|null $navigationGroup = 'People';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?int $navigationSort = 21;

    protected static ?string $slug = 'administration-categories';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('administration::administration.category.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('administration::administration.category.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TranslatableTabs::make()->columnSpanFull()->schema(fn (string $locale, bool $isDefault): array => [
                TextInput::make("name.{$locale}")->label(__('administration::administration.fields.name'))->required($isDefault)->maxLength(100),
            ]),
            Grid::make(3)->columnSpanFull()->schema([
                Select::make('group')->label(__('administration::administration.fields.group'))->required()->live()
                    ->options(static::groups())->default('officer')
                    ->afterStateUpdated(fn ($state, callable $set) => $set('is_public', $state !== 'support')),
                Toggle::make('is_public')->label(__('administration::administration.fields.is_public'))
                    ->helperText(__('administration::administration.help.is_public'))->default(true)->inline(false),
                Toggle::make('is_active')->label(__('platform::relations.active'))->default(true)->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('members'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label(__('administration::administration.fields.name'))->searchable(),
                TextColumn::make('group')->label(__('administration::administration.fields.group'))->badge()
                    ->formatStateUsing(fn (string $state): string => static::groups()[$state] ?? $state),
                IconColumn::make('is_public')->label(__('administration::administration.fields.is_public'))->boolean(),
                TextColumn::make('members_count')->label(__('administration::administration.plural'))->numeric(),
                ToggleColumn::make('is_active')->label(__('platform::relations.active')),
            ])
            ->filters([SelectFilter::make('group')->label(__('administration::administration.fields.group'))->options(static::groups())])
            ->recordActions([EditAction::make(), DeleteAction::make()->hidden(fn (AdministrationCategory $record): bool => $record->members_count > 0)]);
    }

    /** @return array<string, string> */
    public static function groups(): array
    {
        return collect(AdministrationCategory::GROUPS)->mapWithKeys(fn (string $g): array => [$g => __("administration::administration.groups.{$g}")])->all();
    }

    public static function getPages(): array
    {
        return ['index' => ManageAdministrationCategories::route('/')];
    }
}
