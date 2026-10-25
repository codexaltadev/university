<?php

namespace Modules\Waiver\Support;

use Illuminate\Support\Str;
use Modules\Tuition\Models\Tuition;
use Modules\Waiver\Models\WaiverCategory;

/**
 * The Waiver Calculator form's figures, ready for a theme (`$calc` in waiver::livewire.waiver-calculator):
 * the programs to pick, a tile per waiver category (DIU's icon by its name), the chosen program's
 * duration / credits / total, and the answer as DIU's result panel shows it (money in the fee
 * category's currency, ranges where the credits vary). Themes hold no PHP logic (T-1).
 */
class CalculatorForm
{
    /** DIU's tile icons, by words in the category's name (the first that matches; else "award") */
    public const ICONS = [
        'sibling|spouse|relative|alumni|family' => 'users',
        'college|school|institute' => 'graduation-cap',
        'female|women|girl' => 'star',
        'physical|challenged|disab|freedom' => 'shield',
        'tribal|ethnic|international|foreign|abroad' => 'globe',
        'diploma' => 'book-open',
        'transfer|credit' => 'trending-up',
    ];

    /** Tiles shown before "Show all" */
    public const TILES = 6;

    public function __construct(protected array $data) {}

    /**
     * The programs to search, by faculty (the faculties in their order of first appearance, the programs in theirs);
     * `terms` = what a search matches (name, short names, department, faculty) in lower case.
     *
     * @return list<array{id: int, label: string, group: string, terms: string}>
     */
    public function programs(): array
    {
        return collect($this->data['tuitions'] ?? [])->map(function (Tuition $t): array {
            $program = $t->program;
            $faculty = $program->faculty();
            $label = (string) $program->name.(filled($t->label) ? ' ('.$t->label.')' : '');

            return [
                'id' => (int) $t->id,
                'label' => $label,
                'group' => (string) ($faculty?->short_name ?: $faculty?->name ?: ''),
                'terms' => Str::lower(implode(' ', array_filter([$label, $program->short_name, $program->department?->short_name, $faculty?->short_name, (string) $faculty?->name]))),
            ];
        })->groupBy('group', preserveKeys: false)->flatten(1)->values()->all();
    }

    /** The chosen program's name (the search box shows it) */
    public function programLabel(): ?string
    {
        return collect($this->programs())->firstWhere('id', (int) ($this->data['tuitionId'] ?? 0))['label'] ?? null;
    }

    /** @return list<array{id: int, name: string, short: string, text: ?string, icon: string, selected: bool}> */
    public function tiles(): array
    {
        $chosen = (int) ($this->data['waiverCategoryId'] ?? 0);

        return collect($this->data['waiverCategories'] ?? [])->map(fn (WaiverCategory $c): array => [
            'id' => (int) $c->id,
            'name' => (string) $c->name,
            'short' => Str::limit((string) $c->name, 40),
            'text' => filled($c->description) ? (string) $c->description : null,
            'icon' => static::icon((string) $c->name),
            'selected' => $chosen === (int) $c->id,
        ])->values()->all();
    }

    /** How many tiles show before "Show all" (all when the chosen one is further down) */
    public function visibleTiles(): int
    {
        $tiles = $this->tiles();
        $chosen = array_search(true, array_column($tiles, 'selected'), true);

        return $chosen !== false && $chosen >= self::TILES ? count($tiles) : min(self::TILES, count($tiles));
    }

    public static function icon(string $name): string
    {
        foreach (self::ICONS as $words => $icon) {
            if (preg_match('/'.$words.'/i', $name)) {
                return $icon;
            }
        }

        return 'award';
    }

    /** The chosen program's facts under its select. @return array{duration: string, credits: string, total: string}|null */
    public function program(): ?array
    {
        $tuition = collect($this->data['tuitions'] ?? [])->firstWhere('id', (int) ($this->data['tuitionId'] ?? 0));
        if (! $tuition instanceof Tuition) {
            return null;
        }
        $money = fn ($v): ?string => $v === null ? null : $tuition->category?->money($v);

        return [
            'duration' => filled($tuition->duration_label) ? (string) $tuition->duration_label : __('waiver::waiver.calculator.na'),
            'credits' => $tuition->creditsText() ?? __('waiver::waiver.calculator.na'),
            'total' => $tuition->total_min === null ? __('waiver::waiver.calculator.na')
                : $money($tuition->total_min).($tuition->hasRange() ? ' – '.$money($tuition->total_max) : ''),
        ];
    }

    public function goldenSsc(): bool
    {
        return (float) ($this->data['ssc'] ?? 0) === 5.0;
    }

    public function goldenHsc(): bool
    {
        return ! ($this->data['diploma'] ?? false) && (float) ($this->data['hsc'] ?? 0) === 5.0;
    }

    /** Why there is no waiver, when Calculate found none */
    public function sorry(): ?string
    {
        return match ($this->data['answer']['status'] ?? null) {
            WaiverResult::NO_WAIVER => __('waiver::waiver.calculator.no_waiver'),
            WaiverResult::NO_TUITION => __('waiver::waiver.calculator.no_tuition'),
            default => null,
        };
    }

    /**
     * The result panel when there is a waiver.
     *
     * @return array{percent: string, category: string, message: string, program: string, duration: ?string, credits: ?string,
     *     total: string, tuition: string, other: string, waiver: string, admission: ?string, final: string, range: bool, condition: ?string}|null
     */
    public function result(): ?array
    {
        $answer = $this->data['answer'] ?? null;
        $tuition = $this->data['tuition'] ?? null;
        if (($answer['status'] ?? null) !== WaiverResult::OK || ! $tuition instanceof Tuition) {
            return null;
        }
        $money = fn ($v): string => (string) $tuition->category?->money($v);
        $range = fn (string $key): string => $money($answer['min'][$key]).(isset($answer['max'][$key]) ? ' – '.$money($answer['max'][$key]) : '');
        $percent = Tuition::plain($answer['percent']).'%';
        $rule = $this->data['rule'] ?? null;

        return [
            'percent' => $percent,
            'category' => (string) $answer['category'],
            'message' => __('waiver::waiver.calculator.eligible', ['percent' => $percent, 'category' => $answer['category']]),
            'program' => (string) $tuition->program->name,
            'duration' => filled($tuition->duration_label) ? (string) $tuition->duration_label : null,
            'credits' => $tuition->creditsText(),
            'total' => $range('total'),
            'tuition' => $range('tuition'),
            'other' => $range('other'),
            'waiver' => $range('waiver'),
            'admission' => $answer['min']['admission'] ? $money($answer['min']['admission']) : null,
            'final' => $range('final'),
            'range' => isset($answer['max']),
            'condition' => filled($rule?->message) ? trim((string) $rule->message) : null,
        ];
    }
}
