<?php

namespace Modules\Waiver\Filament\Resources\Boards;

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
use Modules\Waiver\Filament\Clusters\WaiverCalculator;
use Modules\Waiver\Filament\Resources\Boards\Pages\ManageBoards;
use Modules\Waiver\Models\Board;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Taxonomy\Vocabularies;

/** Academic › Boards (FR-FEE-05): education boards per program level. */
class BoardResource extends Resource
{
    protected static ?string $model = Board::class;

    protected static ?string $cluster = WaiverCalculator::class;   // Academic › Waiver Calculator

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('waiver::waiver.board.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('waiver::waiver.board.plural');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                TextInput::make("name.{$locale}")->label(__('waiver::waiver.fields.name'))->required($isDefault)->maxLength(150),
            ]),
            Select::make('level_term_id')->label(__('waiver::waiver.fields.level'))->required()
                ->options(fn (): array => app(Vocabularies::class)->options('program.level')),
            Toggle::make('is_active')->label(__('waiver::waiver.fields.active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('level'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label(__('waiver::waiver.fields.name')),
                TextColumn::make('level.name')->label(__('waiver::waiver.fields.level'))->badge(),
                ToggleColumn::make('is_active')->label(__('waiver::waiver.fields.active')),
            ])
            ->filters([
                SelectFilter::make('level_term_id')->label(__('waiver::waiver.fields.level'))->options(fn (): array => app(Vocabularies::class)->options('program.level', activeOnly: false)),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageBoards::route('/')];
    }
}
