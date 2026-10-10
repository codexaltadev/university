<?php

namespace Modules\Fee\Filament\Resources\Tuitions\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Modules\Fee\Models\FeeCategory;
use Modules\Fee\Models\Tuition;
use Modules\Fee\Models\TuitionLine;
use Modules\Program\Models\Program;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Scope\UserScopeFilter;

/**
 * Tuition form (FR-FEE-02): program, fee category, credits, semesters, duration; the fees
 * as lines; totals worked out from them (AC-FEE-03) unless entered by hand.
 */
class TuitionForm
{
    public static function configure(Schema $schema): Schema
    {
        $manual = fn (Get $get): bool => (bool) $get('totals_overridden');

        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Grid::make(1)->columnSpan(2)->schema([
                    Section::make(__('fee::fee.sections.tuition'))->columns(2)->schema([
                        Select::make('program_id')->label(__('fee::fee.fields.program'))->required()->searchable()
                            ->options(fn (?Tuition $record): array => static::programOptions($record))
                            ->default(fn (): ?int => request()->integer('program') ?: null)      // from the Program Hub (FR-GEN-86)
                            ->dehydrated()->disabled(fn ($livewire): bool => filled($livewire->hubProgram ?? null)),
                        Select::make('fee_category_id')->label(__('fee::fee.fields.category'))->required()
                            ->options(fn (): array => FeeCategory::query()->ordered()->get()->mapWithKeys(fn (FeeCategory $c): array => [$c->id => $c->name])->all()),
                        TextInput::make('credit_min')->label(__('fee::fee.fields.credit_min'))->numeric()->minValue(0)->maxValue(9999)->live(onBlur: true),
                        TextInput::make('credit_max')->label(__('fee::fee.fields.credit_max'))->numeric()->minValue(0)->maxValue(9999)->gte('credit_min'),
                        TextInput::make('total_terms')->label(__('fee::fee.fields.total_terms'))->integer()->minValue(1)->maxValue(255),
                        TextInput::make('prospectus_url')->label(__('fee::fee.fields.prospectus_url'))->url()->maxLength(2048),
                        TranslatableTabs::make()->schema(fn (string $locale): array => [
                            TextInput::make("duration_label.{$locale}")->label(__('fee::fee.fields.duration_label'))->placeholder('4 Years')->maxLength(100),
                            TextInput::make("label.{$locale}")->label(__('fee::fee.fields.label'))->maxLength(100),
                        ]),
                    ]),
                    Section::make(__('fee::fee.sections.lines'))->schema([
                        Repeater::make('lines')->relationship()->hiddenLabel()->orderColumn('sort_order')->defaultItems(0)
                            ->columns(4)->addActionLabel(__('fee::fee.sections.lines').' +')
                            ->itemLabel(fn (array $state): ?string => isset($state['line_type']) ? __("fee::fee.line_types.{$state['line_type']}") : null)
                            ->schema([
                                Select::make('line_type')->label(__('fee::fee.fields.line_type'))->required()
                                    ->options(collect(TuitionLine::TYPES)->mapWithKeys(fn (string $t): array => [$t => __("fee::fee.line_types.{$t}")])->all()),
                                ...TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                                    TextInput::make("label.{$locale}")->label(__('fee::fee.fields.line_label')." ({$language})")->required($isDefault)->maxLength(100),
                                ]),
                                TextInput::make('amount')->label(__('fee::fee.fields.amount'))->required()->numeric()->minValue(0),
                                TextInput::make('amount_max')->label(__('fee::fee.fields.amount_max'))->numeric()->minValue(0)->gte('amount'),
                            ]),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    Section::make(__('fee::fee.sections.totals'))->description(__('fee::fee.help.totals'))->compact()->schema([
                        Toggle::make('totals_overridden')->label(__('fee::fee.fields.totals_overridden'))->live(),
                        ...collect(['tuition_min', 'tuition_max', 'total_min', 'total_max'])->map(fn (string $field) => TextInput::make($field)
                            ->label(__("fee::fee.fields.{$field}"))->numeric()->minValue(0)
                            ->disabled(fn (Get $get): bool => ! $manual($get))->dehydrated($manual))->all(),
                    ]),
                    Section::make()->compact()->schema([
                        Toggle::make('is_active')->label(__('fee::fee.fields.active'))->default(true),
                    ]),
                ]),
            ]),
        ]);
    }

    /**
     * Programs the user may set fees for (scope), plus the current one.
     *
     * @return array<int, string>
     */
    protected static function programOptions(?Tuition $record): array
    {
        $label = fn (Program $p): string => "{$p->short_name} — {$p->name}";
        $options = Program::query()->ordered()->get()->mapWithKeys(fn (Program $p): array => [$p->id => $label($p)])->all();

        if ($record?->program_id && ! isset($options[$record->program_id])) {
            $current = UserScopeFilter::withoutScope(fn () => Program::query()->withTrashed()->find($record->program_id));
            $options = $current ? [$current->id => $label($current)] + $options : $options;
        }

        return $options;
    }
}
