<?php

namespace Modules\Notification\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Modules\Notification\Models\ReminderState;
use Modules\Notification\Support\Reminders;
use Platform\Models\UserScope;
use Platform\Scope\UserScopeFilter;

/**
 * Dashboard (FR-NOTI-16): "Your department's Notices have not been updated for
 * 95 days" — the empty / stale content of the user's own entities.
 */
class MyContentFreshness extends Widget
{
    protected string $view = 'notification::widget';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::check() && static::lines() !== [];
    }

    /** @return list<string> */
    public static function lines(): array
    {
        $scopes = UserScope::query()->where('user_id', Auth::id())->get(['scopeable_type', 'scopeable_id']);

        if ($scopes->isEmpty()) {
            return [];
        }

        $reminders = app(Reminders::class);
        $sources = $reminders->sources();

        return ReminderState::query()->with('rule')->where('status', '!=', ReminderState::OK)
            ->where(function ($q) use ($scopes): void {
                foreach ($scopes as $scope) {
                    $q->orWhere(fn ($w) => $w->where('entity_type', $scope->scopeable_type)->where('entity_id', $scope->scopeable_id));
                }
            })
            ->get()
            ->filter(fn (ReminderState $state): bool => $state->rule?->is_active && isset($sources[$state->rule->module_slug]))
            ->map(function (ReminderState $state) use ($reminders, $sources): string {
                $entity = $state->entity()->withoutGlobalScope(UserScopeFilter::class)->first();
                $name = $entity ? $reminders->entityName($entity) : '';
                $module = $sources[$state->rule->module_slug]::label();

                return $state->status === ReminderState::EMPTY
                    ? __('notification::reminder.widget.empty', ['entity' => $name, 'module' => $module])
                    : __('notification::reminder.widget.stale', ['entity' => $name, 'module' => $module, 'days' => $state->days()]);
            })
            ->values()->all();
    }
}
