<?php

namespace Modules\Fee\Filament\Resources\Boards;

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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Fee\Filament\Resources\Boards\Pages\ManageBoards;
use Modules\Fee\Models\Board;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Taxonomy\Vocabularies;
use UnitEnum;

/** Academic › Boards (FR-FEE-05): education boards per program level. */
class BoardResource extends Resource
{
    protected static ?string $model = Board::class;

    protected static string|UnitEnum|null $navigationGroup = 'Academic';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?int $navigationSort = 54;

    public static function getModelLabel(): string
    {
        return __('fee::fee.board.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('fee::fee.board.plural');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                TextInput::make("name.{$locale}")->label(__('fee::fee.fields.name'))->required($isDefault)->maxLength(150),
            ]),
            Select::make('level_term_id')->label(__('fee::fee.fields.level'))->required()
                ->options(fn (): array => app(Vocabularies::class)->options('program.level')),
            Toggle::make('is_active')->label(__('fee::fee.fields.active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('level'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label(__('fee::fee.fields.name')),
                TextColumn::make('level.name')->label(__('fee::fee.fields.level'))->badge(),
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
        return ['index' => ManageBoards::route('/')];
    }
}
