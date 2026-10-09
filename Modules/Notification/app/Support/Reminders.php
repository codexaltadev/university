<?php

namespace Modules\Notification\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Notification\Models\ReminderRule;
use Modules\Notification\Models\ReminderState;
use Modules\Notification\Types\ContentReminderNotice;
use Modules\Notification\Types\ReminderSummaryNotice;
use Platform\Notifications\FreshnessSource;
use Platform\Notifications\Notifications;
use Platform\Notifications\Recipients;
use Platform\Registry\Registry;
use Platform\Scope\UserScopeFilter;

/**
 * Content Update Reminder (BRD 7.10, FR-NOTI-09..16). Daily: every active rule ×
 * every active entity of its type → ok / empty / stale; not ok and the interval
 * has passed → a reminder to the entity's responsible users. Weekly: one summary
 * for the admins. Rules of inactive modules, inactive entities and inactive
 * rules are skipped (BRL-NOTI-02).
 */
class Reminders
{
    public function __construct(protected Registry $registry) {}

    /** @return array<string, FreshnessSource> module slug => source, of active modules */
    public function sources(): array
    {
        return collect($this->registry->all('freshnessSource'))
            ->map(fn (array $entry): FreshnessSource => app($entry['class']))
            ->mapWithKeys(fn (FreshnessSource $source): array => [$source::module() => $source])
            ->all();
    }

    /** @return array<string, string> morph alias => label: entities content can be shown in */
    public function entityTypes(): array
    {
        return collect($this->registry->all('relationTarget'))->pluck('model')->unique()
            ->mapWithKeys(fn (string $model): array => [(new $model)->getMorphClass() => Str::headline(class_basename($model))])
            ->all();
    }

    /** @return array{checked: int, reminded: int} */
    public function check(?Carbon $now = null): array
    {
        $now ??= now();
        $sources = $this->sources();
        $result = ['checked' => 0, 'reminded' => 0];

        foreach (ReminderRule::query()->where('is_active', true)->get() as $rule) {
            $source = $sources[$rule->module_slug] ?? null;
            $class = Relation::getMorphedModel($rule->entity_type);

            if ($source === null || $class === null) {
                continue;   // module off or entity type gone (BRL-NOTI-02)
            }

            $entities = $class::query()->withoutGlobalScope(UserScopeFilter::class)->where('is_active', true)->get();

            foreach ($entities as $entity) {
                $state = $this->evaluate($rule, $source, $entity, $now);
                $result['checked']++;

                if ($state->status !== ReminderState::OK && $this->due($rule, $state, $now) && $this->remind($rule, $source, $entity, $state)) {
                    $state->forceFill(['last_reminded_at' => $now, 'reminder_count' => $state->reminder_count + 1])->save();
                    $result['reminded']++;
                }
            }

            // States of entities that are no longer active go (BRL-NOTI-02)
            $rule->states()->whereNotIn('entity_id', $entities->modelKeys())->delete();
        }

        return $result;
    }

    /** FR-NOTI-11/14: the state of one entity for one rule, saved. */
    public function evaluate(ReminderRule $rule, FreshnessSource $source, Model $entity, Carbon $now): ReminderState
    {
        $cutoff = $now->copy()->subDays($rule->max_days_without_update);
        $latest = $source->latestFor($entity);
        $staleIds = null;

        if ($latest === null) {
            $status = $rule->is_mandatory ? ReminderState::EMPTY : ReminderState::OK;
        } elseif ($rule->check_mode === 'each_record') {
            $staleIds = $source->staleRecords($entity, $cutoff)->modelKeys();
            $status = $staleIds === [] ? ReminderState::OK : ReminderState::STALE;
        } else {
            $status = $latest->lt($cutoff) ? ReminderState::STALE : ReminderState::OK;
        }

        $state = ReminderState::query()->firstOrNew([
            'rule_id' => $rule->id, 'entity_type' => $entity->getMorphClass(), 'entity_id' => $entity->getKey(),
        ]);

        $state->fill([
            'status' => $status,
            'last_content_at' => $latest,
            'stale_records' => $staleIds ?: null,
            'checked_at' => $now,
        ]);

        if ($status === ReminderState::OK) {
            $state->fill(['last_reminded_at' => null, 'reminder_count' => 0]);   // updated: reminders stop
        }

        $state->save();

        return $state;
    }

    /** FR-NOTI-13: one summary of every empty / stale entity, for the admins. */
    public function summary(): int
    {
        $open = ReminderState::query()->with('rule')->where('status', '!=', ReminderState::OK)
            ->whereHas('rule', fn ($q) => $q->where('is_active', true))->get()
            ->filter(fn (ReminderState $state): bool => isset($this->sources()[$state->rule->module_slug]));

        if ($open->isEmpty()) {
            return 0;
        }

        return app(Notifications::class)->send(new ReminderSummaryNotice(['lines' => $this->summaryLines($open)]));
    }

    /**
     * @param  Collection<int, ReminderState>  $states
     * @return list<string>
     */
    public function summaryLines(Collection $states): array
    {
        $sources = $this->sources();

        return $states->map(function (ReminderState $state) use ($sources): string {
            $entity = $state->entity()->withoutGlobalScope(UserScopeFilter::class)->first();
            $source = $sources[$state->rule->module_slug];
            $nobody = $entity && Recipients::responsibleFor($entity, $source::permission())->isEmpty();

            return sprintf('%s — %s: %s%s%s',
                $entity ? $this->entityName($entity) : '#'.$state->entity_id,
                $source::label(),
                $state->status === ReminderState::EMPTY ? __('notification::reminder.status.empty') : __('notification::reminder.status.stale'),
                $state->days() !== null ? ' ('.trans_choice('notification::reminder.days', $state->days(), ['count' => $state->days()]).')' : '',
                $nobody ? ' — '.__('notification::reminder.no_responsible') : '',   // BRL-NOTI-03
            );
        })->values()->all();
    }

    public function entityName(Model $entity): string
    {
        $attribute = $entity::$titleAttribute ?? 'name';

        return (string) (method_exists($entity, 'getTranslation') && in_array($attribute, $entity->translatable ?? [], true)
            ? $entity->getTranslation($attribute, app()->getLocale())
            : $entity->getAttribute($attribute));
    }

    protected function due(ReminderRule $rule, ReminderState $state, Carbon $now): bool
    {
        return $state->last_reminded_at === null || $state->last_reminded_at->lte($now->copy()->subDays($rule->remind_every_days));
    }

    /** FR-NOTI-12: the entity's responsible users, in the bell and by email. */
    protected function remind(ReminderRule $rule, FreshnessSource $source, Model $entity, ReminderState $state): bool
    {
        $stale = $state->stale_records
            ? $source::model()::query()->withoutGlobalScope(UserScopeFilter::class)->whereKey($state->stale_records)->get()
                ->map(fn (Model $record): string => '• '.$source->title($record).(($url = $source->editUrl($record)) ? " — {$url}" : ''))
                ->all()
            : [];

        $sent = app(Notifications::class)->send(new ContentReminderNotice([
            'entity' => $this->entityName($entity),
            'module' => $source::label(),
            'days' => (string) ($state->days() ?? ''),
            'records' => implode("\n", $stale),
            'status' => $state->status,
            'rule' => $rule,
            'permission' => $source::permission(),
        ], $entity));

        return $sent > 0;
    }
}
