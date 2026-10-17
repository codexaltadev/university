<?php

namespace Modules\Newsletter\Console;

use Illuminate\Console\Command;
use Modules\Newsletter\Models\Campaign;
use Modules\Newsletter\Support\CampaignSender;

/** FR-NLT-05: every minute — the scheduled campaigns whose time has come start sending. */
class DispatchCampaignsCommand extends Command
{
    protected $signature = 'newsletter:dispatch';

    protected $description = 'Start the scheduled newsletter campaigns that are due';

    public function handle(CampaignSender $sender): int
    {
        $started = 0;
        foreach (Campaign::query()->due()->get() as $campaign) {
            $sender->start($campaign);
            $started++;
        }
        $this->components->info("{$started} campaign(s) started.");

        return self::SUCCESS;
    }
}
