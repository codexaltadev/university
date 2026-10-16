<?php

namespace Modules\Leadership\Filament\Resources\Positions;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Leadership\Filament\Resources\Positions\Pages\CreatePosition;
use Modules\Leadership\Filament\Resources\Positions\Pages\EditPosition;
use Modules\Leadership\Filament\Resources\Positions\Pages\ListPositions;
use Modules\Leadership\Filament\Resources\Positions\RelationManagers\TermsRelationManager;
use Modules\Leadership\Models\LeadershipTerm;
use Modules\Leadership\Models\Position;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use Platform\Taxonomy\Vocabularies;
use UnitEnum;

/** People › Leadership (M-15, FR-LEAD-01/02): positions by group, in order; who holds them below each position. */
class PositionResource extends Resource
{
    protected static ?string $model = Position::class;

    protected static string|UnitEnum|null $navigationGroup = 'People';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?int $navigationSort = 22;

    protected static ?string $slug = 'leadership';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('leadership::leadership.position.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('leadership::leadership.plural');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columnSpanFull()->schema([
                TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                    TextInput::make("title.{$locale}")->label(__('leadership::leadership.fields.title'))->required($isDefault)->maxLength(150)
                        ->helperText($isDefault ? __('leadership::leadership.help.title') : null),
                ]),
                Grid::make(3)->schema([
                    Select::make('group_term_id')->label(__('leadership::leadership.fields.group'))
                        ->options(fn (): array => app(Vocabularies::class)->options(Position::GROUP)),
                    Toggle::make('is_single_holder')->label(__('leadership::leadership.fields.is_single_holder'))
                        ->helperText(__('leadership::leadership.help.is_single_holder'))->default(true)->inline(false)
                        ->disabled(fn (?Position $record): bool => (bool) $record?->terms()->exists()),   // the role of existing terms
                    Toggle::make('is_active')->label(__('platform::relations.active'))->default(true)->inline(false),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('group'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('title')->label(__('leadership::leadership.fields.title'))->searchable(),
                TextColumn::make('group.name')->label(__('leadership::leadership.fields.group'))->badge(),
                TextColumn::make('holders')->label(__('leadership::leadership.current'))->wrap()
                    ->state(fn (Position $record): string => $record->terms()->current()->get()->map(fn (LeadershipTerm $t): string => $t->name())->filter()->implode(', '))
                    ->placeholder(__('leadership::leadership.vacant')),
                ToggleColumn::make('is_active')->label(__('platform::relations.active')),
            ])
            ->filters([
                SelectFilter::make('group_term_id')->label(__('leadership::leadership.fields.group'))
                    ->options(fn (): array => app(Vocabularies::class)->options(Position::GROUP)),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }

    public static function getRelations(): array
    {
        return [TermsRelationManager::class, ActivityRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPositions::route('/'),
            'create' => CreatePosition::route('/create'),
            'edit' => EditPosition::route('/{record}/edit'),
        ];
    }
}
