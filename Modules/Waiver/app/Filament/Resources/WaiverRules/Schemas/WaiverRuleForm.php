<?php

namespace Modules\Waiver\Filament\Resources\WaiverRules\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Modules\Tuition\Models\FeeCategory;
use Modules\Tuition\Models\Tuition;
use Modules\Waiver\Models\WaiverCategory;
use Modules\Waiver\Models\WaiverRule;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Taxonomy\Vocabularies;

/**
 * Waiver rule form (FR-FEE-05, BRL-FEE-01): level and fee category; result ranges
 * (GPA 0–5, CGPA 0–4, min ≤ max); waiver percents; what it applies to — none chosen = all.
 * Categories keep their order (the first names the result); genders are saved by the pages.
 */
class WaiverRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        $gpa = fn (string $name, int $max, ?string $gte = null) => TextInput::make($name)->label(__("waiver::waiver.fields.{$name}"))
            ->numeric()->minValue(0)->maxValue($max)->step(0.01)->when($gte, fn (TextInput $f) => $f->gte($gte));

        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Grid::make(1)->columnSpan(2)->schema([
                    Section::make(__('waiver::waiver.rule.sections.rule'))->columns(2)->schema([
                        TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                            TextInput::make("name.{$locale}")->label(__('waiver::waiver.fields.name'))->required($isDefault)->maxLength(255),
                            Textarea::make("message.{$locale}")->label(__('waiver::waiver.fields.message'))->rows(2)->maxLength(1000),
                        ]),
                        Select::make('level_term_id')->label(__('waiver::waiver.fields.level'))->required()->live()
                            ->options(fn (): array => app(Vocabularies::class)->options('program.level')),
                        Select::make('fee_category_id')->label(__('waiver::waiver.fields.category'))->live()
                            ->options(fn (): array => FeeCategory::query()->ordered()->get()->mapWithKeys(fn (FeeCategory $c): array => [$c->id => $c->name])->all()),
                    ]),
                    Section::make(__('waiver::waiver.rule.sections.results'))->columns(2)->schema([
                        $gpa('ssc_min', 5), $gpa('ssc_max', 5, 'ssc_min'),
                        $gpa('hsc_min', 5), $gpa('hsc_max', 5, 'hsc_min'),
                        Toggle::make('require_ssc_golden')->label(__('waiver::waiver.fields.require_ssc_golden')),
                        Toggle::make('require_hsc_golden')->label(__('waiver::waiver.fields.require_hsc_golden')),
                        $gpa('ug_cgpa_min', 4), $gpa('ug_cgpa_max', 4, 'ug_cgpa_min'),
                    ]),
                    Section::make(__('waiver::waiver.rule.sections.applies'))->columns(2)->schema([
                        Select::make('waiver_category_ids')->label(__('waiver::waiver.fields.waiver_categories'))->multiple()->columnSpanFull()
                            ->options(fn (Get $get): array => WaiverCategory::query()->where('level_term_id', $get('level_term_id'))->ordered()->get()
                                ->mapWithKeys(fn (WaiverCategory $c): array => [$c->id => $c->name])->all()),
                        Select::make('boards')->label(__('waiver::waiver.fields.boards'))->multiple()
                            ->relationship('boards', modifyQueryUsing: fn (Builder $query, Get $get) => $query->where('waiver_boards.level_term_id', $get('level_term_id')))
                            ->getOptionLabelFromRecordUsing(fn ($record): string => (string) $record->name),   // Filament injects by name
                        CheckboxList::make('genders')->label(__('waiver::waiver.fields.genders'))->columns(3)
                            ->options(collect(WaiverRule::GENDERS)->mapWithKeys(fn (string $g): array => [$g => __("waiver::waiver.genders.{$g}")])->all()),
                        Select::make('faculties')->label(__('waiver::waiver.fields.faculties'))->multiple()->preload()
                            ->relationship('faculties', 'short_name'),
                        Select::make('tuitions')->label(__('waiver::waiver.fields.tuitions'))->multiple()->searchable()
                            ->relationship('tuitions', modifyQueryUsing: fn (Builder $query, Get $get) => $query->with('program')
                                ->when($get('fee_category_id'), fn ($q, $c) => $q->where('tuition_fees.fee_category_id', $c))
                                ->whereHas('program', fn ($p) => $p->where('level_term_id', $get('level_term_id'))))
                            ->getOptionLabelFromRecordUsing(fn (Tuition $record): string => trim($record->program?->name.' '.($record->label ? "({$record->label})" : ''))),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    Section::make(__('waiver::waiver.rule.sections.waiver'))->compact()->schema([
                        TextInput::make('waiver_percent')->label(__('waiver::waiver.fields.waiver_percent'))->required()->numeric()->minValue(0)->maxValue(100)->suffix('%'),
                        TextInput::make('improved_waiver_percent')->label(__('waiver::waiver.fields.improved_waiver_percent'))->numeric()->minValue(0)->maxValue(100)->suffix('%'),
                        TextInput::make('max_waiver_percent')->label(__('waiver::waiver.fields.max_waiver_percent'))->numeric()->minValue(0)->maxValue(100)->suffix('%'),
                        TextInput::make('priority')->label(__('waiver::waiver.fields.priority'))->integer()->default(0),
                        Toggle::make('is_active')->label(__('waiver::waiver.fields.active'))->default(true),
                    ]),
                ]),
            ]),
        ]);
    }
}
