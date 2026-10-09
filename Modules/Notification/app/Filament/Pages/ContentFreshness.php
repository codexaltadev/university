<?php

namespace Modules\Notification\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Modules\Notification\Models\ReminderRule;
use Modules\Notification\Models\ReminderState;
use Modules\Notification\Support\Reminders;
use Platform\Scope\UserScopeFilter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Site › Content freshness (FR-NOTI-15, UI/UX Part A §7.13): entity × content
 * matrix — OK / empty / stale with days and reminders sent; filter by entity type,
 * export as CSV, check now.
 */
class ContentFreshness extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static ?int $navigationSort = 82;

    protected static ?string $slug = 'content-freshness';

    protected string $view = 'notification::freshness';

    #[Url]
    public ?string $type = null;

    public static function getNavigationGroup(): ?string
    {
        return 'Site';
    }

    public static function getNavigationLabel(): string
    {
        return __('notification::reminder.report.title');
    }

    public function getTitle(): string
    {
        return __('notification::reminder.report.title');
    }

    public static function canAccess(): bool
    {
        return (bool) Auth::user()?->can('viewAny', ReminderRule::class);
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $types = array_keys($this->types());
        $this->type = in_array($this->type, $types, true) ? $this->type : ($types[0] ?? null);
    }

    /** @return array<string, string> entity types that have rules */
    public function types(): array
    {
        $labels = app(Reminders::class)->entityTypes();

        return ReminderRule::query()->distinct()->pluck('entity_type')
            ->mapWithKeys(fn (string $type): array => [$type => $labels[$type] ?? $type])->all();
    }

    /**
     * @return array{columns: array<int, string>, rows: list<array{name: string, cells: array<int, ?ReminderState>}>}
     */
    public function matrix(): array
    {
        $reminders = app(Reminders::class);
        $sources = $reminders->sources();
        $rules = ReminderRule::query()->where('entity_type', $this->type)->where('is_active', true)->get()
            ->filter(fn (ReminderRule $rule): bool => isset($sources[$rule->module_slug]))->keyBy('id');

        $states = ReminderState::query()->whereIn('rule_id', $rules->keys())->get()->groupBy('entity_id');
        $entities = $this->type && ($class = Relation::getMorphedModel($this->type))
            ? $class::query()->withoutGlobalScope(UserScopeFilter::class)->whereKey($states->keys())->get()
            : collect();

        return [
            'columns' => $rules->map(fn (ReminderRule $rule): string => $sources[$rule->module_slug]::label())->all(),
            'rows' => $entities->map(fn ($entity): array => [
                'name' => $reminders->entityName($entity),
                'cells' => $rules->map(fn (ReminderRule $rule) => $states[$entity->getKey()]?->firstWhere('rule_id', $rule->id))->all(),
            ])->sortBy('name')->values()->all(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('check')->label(__('notification::reminder.actions.check'))->icon(Heroicon::OutlinedArrowPath)
                ->visible(fn (): bool => (bool) Auth::user()?->can('create', ReminderRule::class))
                ->action(function (): void {
                    $result = app(Reminders::class)->check();
                    Notification::make()->success()->title(__('notification::reminder.report.checked', $result))->send();
                }),
            Action::make('export')->label(__('notification::reminder.actions.export'))->icon(Heroicon::OutlinedArrowDownTray)->color('gray')
                ->action(fn (): StreamedResponse => $this->export()),
        ];
    }

    public function export(): StreamedResponse
    {
        $matrix = $this->matrix();

        return response()->streamDownload(function () use ($matrix): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['', ...array_values($matrix['columns'])], escape: '\\');

            foreach ($matrix['rows'] as $row) {
                fputcsv($out, [$row['name'], ...array_map(fn (?ReminderState $s): string => $s ? $s->status.($s->days() !== null ? " ({$s->days()}d)" : '') : '—', array_values($row['cells']))], escape: '\\');
            }

            fclose($out);
        }, 'content-freshness-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
