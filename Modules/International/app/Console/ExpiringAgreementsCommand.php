<?php

namespace Modules\International\Console;

use Illuminate\Console\Command;
use Modules\International\Models\Institution;
use Modules\International\Notifications\AgreementExpiring;
use Platform\Notifications\Notifications;

/** FR-INTL-03: daily (06:10) — the agreements that end in exactly "notify_days" days are reported once. */
class ExpiringAgreementsCommand extends Command
{
    protected $signature = 'international:expiring';

    protected $description = 'Tell the admins about partner agreements that end soon';

    public function handle(Notifications $notifications): int
    {
        $days = max(1, (int) settings('international.notify_days', 60));
        $due = Institution::query()->withoutGlobalScopes()->whereNull('deleted_at')->where('is_active', true)
            ->whereDate('expires_on', today()->addDays($days)->toDateString())->with('country')->get();
        foreach ($due as $institution) {
            $notifications->send(new AgreementExpiring([
                'name' => (string) $institution->name,
                'country' => (string) $institution->country?->name,
                'date' => $institution->expires_on->toDateString(),
                'days' => (string) $days,
            ], $institution));
        }
        $this->components->info("{$due->count()} agreement(s) end in {$days} days.");

        return self::SUCCESS;
    }
}
