<?php

namespace Modules\Fee\Filament\Hub;

use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\Fee\Filament\Resources\WaiverRules\WaiverRuleResource;
use Modules\Fee\Models\Tuition;
use Modules\Fee\Models\WaiverRule;
use Modules\Program\Filament\Resources\Programs\ProgramResource;
use Platform\Filament\Hub\HubRelationPage;

/**
 * "Waiver rules" in the Program Hub (UI/UX Part A §4.3, group "Fees"): the rules that apply
 * to this program — on one of its tuitions, or on its level without a tuition condition.
 * Read only: a rule usually covers many programs, so it is edited in Waiver Calculator ›
 * Rules only. Shown to whoever may view waiver rules (by default the Waiver Office).
 */
class ProgramWaiverRules extends HubRelationPage
{
    protected static string $resource = ProgramResource::class;

    protected static string $relationship = 'tuitions';   // not used: the table query gives the rules

    protected static ?int $navigationSort = 41;

    public static function hubKey(): string
    {
        return 'waiver-rules';
    }

    public static function getNavigationLabel(): string
    {
        return __('fee::fee.rule.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('program::program.hub.fees');
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return Heroicon::OutlinedReceiptPercent;
    }

    public static function canAccess(array $parameters = []): bool
    {
        $program = $parameters['record'] ?? null;
        $user = Auth::user();

        return $program instanceof Model && $user !== null && $user->can('viewAny', WaiverRule::class) && $user->can('view', $program);
    }

    protected function rulesQuery(): Builder
    {
        $program = $this->getOwnerRecord();
        $tuitions = Tuition::query()->where('program_id', $program->getKey())->pluck('id');

        return WaiverRule::query()
            ->where('level_term_id', $program->level_term_id)
            ->where(fn (Builder $q) => $q->whereHas('tuitions', fn (Builder $t) => $t->whereIn('fee_tuitions.id', $tuitions))->orWhereDoesntHave('tuitions'))
            ->with(['feeCategory', 'categories']);
    }

    public function table(Table $table): Table
    {
        $plain = fn ($v): ?string => Tuition::plain($v);

        return $table
            ->query(fn (): Builder => $this->rulesQuery())
            ->defaultSort('waiver_percent', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('fee::fee.fields.name'))->wrap()
                    ->description(fn (WaiverRule $r): ?string => $r->categories->map(fn ($c) => $c->name)->implode(', ') ?: null),
                TextColumn::make('feeCategory.name')->label(__('fee::fee.fields.category')),
                TextColumn::make('waiver_percent')->label(__('fee::fee.fields.waiver_percent'))->sortable()
                    ->formatStateUsing(fn ($state, WaiverRule $r): string => $plain($state).'%'.($r->improved_waiver_percent !== null ? ' → '.$plain($r->improved_waiver_percent).'%' : '')),
                IconColumn::make('is_active')->label(__('fee::fee.fields.active'))->boolean(),
            ])
            ->recordUrl(fn (WaiverRule $r): ?string => Auth::user()?->can('update', $r) ? WaiverRuleResource::getUrl('edit', ['record' => $r]) : null)
            ->headerActions([])
            ->recordActions([]);
    }
}
