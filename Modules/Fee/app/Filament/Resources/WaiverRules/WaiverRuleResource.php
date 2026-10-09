<?php

namespace Modules\Fee\Filament\Resources\WaiverRules;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Fee\Filament\Resources\WaiverRules\Pages\CreateWaiverRule;
use Modules\Fee\Filament\Resources\WaiverRules\Pages\EditWaiverRule;
use Modules\Fee\Filament\Resources\WaiverRules\Pages\ListWaiverRules;
use Modules\Fee\Filament\Resources\WaiverRules\Schemas\WaiverRuleForm;
use Modules\Fee\Models\FeeCategory;
use Modules\Fee\Models\Tuition;
use Modules\Fee\Models\WaiverRule;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use Platform\Taxonomy\Vocabularies;
use UnitEnum;

/** Academic › Waiver rules (FR-FEE-05, FR-FEE-09 rule test). */
class WaiverRuleResource extends Resource
{
    protected static ?string $model = WaiverRule::class;

    protected static string|UnitEnum|null $navigationGroup = 'Academic';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?int $navigationSort = 52;

    public static function getModelLabel(): string
    {
        return __('fee::fee.rule.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('fee::fee.rule.plural');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return WaiverRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        $range = fn ($min, $max): ?string => $min === null ? null : Tuition::plain($min).'–'.Tuition::plain($max);

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['level', 'feeCategory', 'categories']))
            ->defaultSort('id')
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('name')->label(__('fee::fee.fields.name'))->searchable(query: fn (Builder $q, string $s) => $q->where('name', 'like', "%{$s}%"))->wrap()
                    ->description(fn (WaiverRule $r): ?string => $r->categories->map(fn ($c) => $c->name)->implode(', ') ?: null),
                TextColumn::make('level.name')->label(__('fee::fee.fields.level'))->badge(),
                TextColumn::make('feeCategory.name')->label(__('fee::fee.fields.category')),
                TextColumn::make('hsc_min')->label(__('fee::fee.rule.sections.results'))
                    ->state(fn (WaiverRule $r): string => collect([
                        $r->ssc_min !== null ? 'SSC '.$range($r->ssc_min, $r->ssc_max).($r->require_ssc_golden ? '★' : '') : null,
                        $r->hsc_min !== null ? 'HSC '.$range($r->hsc_min, $r->hsc_max).($r->require_hsc_golden ? '★' : '') : null,
                        $r->ug_cgpa_min !== null ? 'CGPA '.$range($r->ug_cgpa_min, $r->ug_cgpa_max) : null,
                    ])->filter()->implode(' · ')),
                TextColumn::make('waiver_percent')->label(__('fee::fee.fields.waiver_percent'))->sortable()
                    ->formatStateUsing(fn ($state, WaiverRule $r): string => Tuition::plain($state).'%'.($r->improved_waiver_percent !== null ? ' → '.Tuition::plain($r->improved_waiver_percent).'%' : '')),
                ToggleColumn::make('is_active')->label(__('fee::fee.fields.active')),
            ])
            ->filters([
                SelectFilter::make('level_term_id')->label(__('fee::fee.fields.level'))->options(fn (): array => app(Vocabularies::class)->options('program.level', activeOnly: false)),
                SelectFilter::make('fee_category_id')->label(__('fee::fee.fields.category'))
                    ->options(fn (): array => FeeCategory::query()->ordered()->get()->mapWithKeys(fn (FeeCategory $c): array => [$c->id => $c->name])->all()),
                TernaryFilter::make('is_active')->label(__('fee::fee.fields.active')),
                TrashedFilter::make(),
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
            'index' => ListWaiverRules::route('/'),
            'create' => CreateWaiverRule::route('/create'),
            'edit' => EditWaiverRule::route('/{record}/edit'),
        ];
    }
}
