<?php

namespace Modules\Waiver\Filament\Resources\WaiverRules\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Modules\Tuition\Models\FeeCategory;
use Modules\Tuition\Models\Tuition;
use Modules\Waiver\Filament\Resources\WaiverRules\WaiverRuleResource;
use Modules\Waiver\Models\Board;
use Modules\Waiver\Models\WaiverCategory;
use Modules\Waiver\Models\WaiverRule;
use Modules\Waiver\Support\WaiverCalculator;
use Platform\Taxonomy\Vocabularies;

class ListWaiverRules extends ListRecords
{
    protected static string $resource = WaiverRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make(), $this->testAction()];
    }

    /** FR-FEE-09: which rules a sample input matches, and what the calculator gives. */
    protected function testAction(): Action
    {
        return Action::make('test')->label(__('waiver::waiver.rule.test'))->icon(Heroicon::OutlinedBeaker)->color('gray')
            ->schema([
                Grid::make(2)->schema([
                    Select::make('fee_category_id')->label(__('waiver::waiver.fields.category'))->required()->live()
                        ->options(fn (): array => FeeCategory::query()->ordered()->get()->mapWithKeys(fn (FeeCategory $c): array => [$c->id => $c->name])->all()),
                    Select::make('level_id')->label(__('waiver::waiver.fields.level'))->required()->live()
                        ->options(fn (): array => app(Vocabularies::class)->options('program.level')),
                    Select::make('tuition_id')->label(__('waiver::waiver.fields.program'))->required()->searchable()
                        ->options(fn (Get $get): array => Tuition::query()->with('program')->where('fee_category_id', $get('fee_category_id'))
                            ->whereHas('program', fn ($p) => $p->where('level_term_id', $get('level_id')))->get()
                            ->mapWithKeys(fn (Tuition $t): array => [$t->id => trim($t->program?->name.' '.($t->label ? "({$t->label})" : ''))])->all()),
                    Select::make('waiver_category_id')->label(__('waiver::waiver.calculator.waiver_category'))->required()
                        ->options(fn (Get $get): array => WaiverCategory::query()->where('level_term_id', $get('level_id'))->ordered()->get()->mapWithKeys(fn ($c): array => [$c->id => $c->name])->all()),
                    Select::make('board_id')->label(__('waiver::waiver.calculator.board'))
                        ->options(fn (Get $get): array => Board::query()->where('level_term_id', $get('level_id'))->ordered()->get()->mapWithKeys(fn ($b): array => [$b->id => $b->name])->all()),
                    Select::make('gender')->label(__('waiver::waiver.calculator.gender'))->required()
                        ->options(collect(WaiverRule::GENDERS)->mapWithKeys(fn (string $g): array => [$g => __("waiver::waiver.genders.{$g}")])->all()),
                    TextInput::make('ssc')->label(__('waiver::waiver.calculator.ssc'))->numeric()->minValue(0)->maxValue(5),
                    TextInput::make('hsc')->label(__('waiver::waiver.calculator.hsc'))->numeric()->minValue(0)->maxValue(5),
                    Toggle::make('ssc_golden')->label(__('waiver::waiver.calculator.ssc').' — '.__('waiver::waiver.calculator.golden')),
                    Toggle::make('hsc_golden')->label(__('waiver::waiver.calculator.hsc').' — '.__('waiver::waiver.calculator.golden')),
                    TextInput::make('ug_cgpa')->label(__('waiver::waiver.calculator.cgpa'))->numeric()->minValue(0)->maxValue(4),
                ]),
            ])
            ->action(function (array $data): void {
                $result = app(WaiverCalculator::class)->calculate([
                    ...$data,
                    'ssc' => filled($data['ssc'] ?? null) ? (float) $data['ssc'] : null,
                    'hsc' => filled($data['hsc'] ?? null) ? (float) $data['hsc'] : null,
                    'ug_cgpa' => filled($data['ug_cgpa'] ?? null) ? (float) $data['ug_cgpa'] : null,
                ]);

                $rules = collect($result->rules);
                Notification::make()
                    ->title($rules->isEmpty() ? __('waiver::waiver.rule.test_none') : __('waiver::waiver.rule.test_result', [
                        'count' => $rules->count(), 'percent' => Tuition::plain($result->percent),
                        'names' => $rules->map(fn (WaiverRule $r): string => "#{$r->id} {$r->name}")->implode(' · '),
                    ]))
                    ->status($rules->isEmpty() ? 'warning' : 'success')->persistent()->send();
            });
    }
}
