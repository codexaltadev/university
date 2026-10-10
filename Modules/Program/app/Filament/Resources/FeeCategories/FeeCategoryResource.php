<?php

namespace Modules\Program\Filament\Resources\FeeCategories;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Unique;
use Modules\Program\Filament\Resources\FeeCategories\Pages\ManageFeeCategories;
use Modules\Program\Models\FeeCategory;
use Platform\Filament\Components\TranslatableTabs;
use UnitEnum;

/** Fees › Fee categories (FR-FEE-01): Local, International … with currency; a small list edited in place. */
class FeeCategoryResource extends Resource
{
    protected static ?string $model = FeeCategory::class;

    protected static string|UnitEnum|null $navigationGroup = 'Academic';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 51;

    public static function getModelLabel(): string
    {
        return __('program::tuition.category.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('program::tuition.category.plural');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                TextInput::make("name.{$locale}")->label(__('program::tuition.fields.name'))->required($isDefault)->maxLength(150),
            ]),
            TextInput::make('code')->label(__('program::tuition.fields.code'))->required()->maxLength(20)->alphaDash()
                ->helperText(__('program::tuition.help.code'))
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule) => $rule->whereNull('deleted_at')),
            Select::make('currency')->label(__('program::tuition.fields.currency'))->required()->default('BDT')
                ->options(array_combine(array_keys(FeeCategory::SYMBOLS), array_keys(FeeCategory::SYMBOLS))),
            Toggle::make('is_active')->label(__('program::tuition.fields.active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label(__('program::tuition.fields.name')),
                TextColumn::make('code')->label(__('program::tuition.fields.code'))->badge(),
                TextColumn::make('currency')->label(__('program::tuition.fields.currency')),
                TextColumn::make('tuitions_count')->counts('tuitions')->label(__('program::tuition.tuition.plural')),
                ToggleColumn::make('is_active')->label(__('program::tuition.fields.active')),
            ])
            ->filters([TrashedFilter::make()])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageFeeCategories::route('/')];
    }
}
