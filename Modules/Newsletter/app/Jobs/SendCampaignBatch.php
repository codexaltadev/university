<?php

namespace Modules\Newsletter\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Newsletter\Models\Campaign;
use Modules\Newsletter\Support\CampaignSender;

/** One minute's batch of a campaign (FR-NLT-05, throttled); queues the next a minute later while some are left. */
class SendCampaignBatch implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $campaignId) {}

    public function handle(CampaignSender $sender): void
    {
        $campaign = Campaign::query()->find($this->campaignId);
        if ($campaign === null || $campaign->send_status !== 'sending') {
            return;
        }
        if ($sender->batch($campaign)) {
            self::dispatch($this->campaignId)->delay(now()->addMinute());
        }
    }
}
