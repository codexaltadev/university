<?php

namespace Modules\Accreditation\Console;

use Illuminate\Console\Command;
use Modules\Accreditation\Models\Accreditation;
use Modules\Accreditation\Notifications\AccreditationExpiring;
use Platform\Notifications\Notifications;

/** FR-ACR-02: daily (06:00) — the accreditations that end in exactly "notify_days" days are reported once. */
class ExpiringAccreditationsCommand extends Command
{
    protected $signature = 'accreditation:expiring';

    protected $description = 'Tell the admins about accreditations that end soon';

    public function handle(Notifications $notifications): int
    {
        $days = max(1, (int) settings('accreditation.notify_days', 60));
        $due = Accreditation::query()->withoutGlobalScopes()->whereNull('deleted_at')->where('is_active', true)
            ->whereDate('expires_on', today()->addDays($days)->toDateString())->get();
        foreach ($due as $accreditation) {
            $notifications->send(new AccreditationExpiring([
                'title' => (string) $accreditation->title,
                'agency' => (string) $accreditation->agency,
                'date' => $accreditation->expires_on->toDateString(),
                'days' => (string) $days,
            ], $accreditation));
        }
        $this->components->info("{$due->count()} accreditation(s) end in {$days} days.");

        return self::SUCCESS;
    }
}
