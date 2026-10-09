<?php

namespace Modules\Fee\Filament\Resources\WaiverCategories;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Fee\Filament\Resources\WaiverCategories\Pages\ManageWaiverCategories;
use Modules\Fee\Models\WaiverCategory;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Taxonomy\Vocabularies;
use UnitEnum;

/**
 * Academic › Waiver categories (FR-FEE-05, DB-03 v1.1): per level; "also matches the
 * rules of" another category (Female → Result Based); a diploma category asks the
 * Diploma CGPA in place of HSC.
 */
class WaiverCategoryResource extends Resource
{
    protected static ?string $model = WaiverCategory::class;

    protected static string|UnitEnum|null $navigationGroup = 'Academic';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = 53;

    public static function getModelLabel(): string
    {
        return __('fee::fee.waiver_category.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('fee::fee.waiver_category.plural');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                TextInput::make("name.{$locale}")->label(__('fee::fee.fields.name'))->required($isDefault)->maxLength(255),
            ]),
            Select::make('level_term_id')->label(__('fee::fee.fields.level'))->required()->live()
                ->options(fn (): array => app(Vocabularies::class)->options('program.level')),
            Select::make('includes')->label(__('fee::fee.fields.includes'))->multiple()
                ->relationship('includes', modifyQueryUsing: fn (Builder $query, Get $get, ?WaiverCategory $record) => $query
                    ->where('fee_waiver_categories.level_term_id', $get('level_term_id'))
                    ->when($record, fn ($q) => $q->whereKeyNot($record->getKey())))
                ->getOptionLabelFromRecordUsing(fn (WaiverCategory $record): string => (string) $record->name),   // Filament injects by name
            Toggle::make('uses_diploma_result')->label(__('fee::fee.fields.uses_diploma_result')),
            Toggle::make('is_active')->label(__('fee::fee.fields.active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['level', 'includes']))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label(__('fee::fee.fields.name'))->wrap()
                    ->description(fn (WaiverCategory $record): ?string => $record->includes->isEmpty() ? null : '+ '.$record->includes->map(fn ($c) => $c->name)->implode(', ')),
                TextColumn::make('level.name')->label(__('fee::fee.fields.level'))->badge(),
                IconColumn::make('uses_diploma_result')->label(__('fee::fee.calculator.diploma'))->boolean(),
                ToggleColumn::make('is_active')->label(__('fee::fee.fields.active')),
            ])
            ->filters([
                SelectFilter::make('level_term_id')->label(__('fee::fee.fields.level'))->options(fn (): array => app(Vocabularies::class)->options('program.level', activeOnly: false)),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageWaiverCategories::route('/')];
    }
}
