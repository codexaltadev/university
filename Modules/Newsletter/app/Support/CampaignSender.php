<?php

namespace Modules\Newsletter\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Newsletter\Jobs\SendCampaignBatch;
use Modules\Newsletter\Mail\CampaignMail;
use Modules\Newsletter\Models\Campaign;
use Modules\Newsletter\Models\Delivery;
use Throwable;

/**
 * Sending a campaign (FR-NLT-05/06): its reachable audience becomes deliveries once (BRL-NLT-01/02),
 * then batches of "per_minute" go out a minute apart on the queue; the counts are the report.
 */
class CampaignSender
{
    public function start(Campaign $campaign): int
    {
        if (! in_array($campaign->send_status, ['draft', 'scheduled'], true)) {
            return 0;
        }
        $now = now();
        $campaign->audience()->select('newsletter_subscribers.id')->chunkById(1000, function ($subscribers) use ($campaign, $now): void {
            Delivery::query()->insertOrIgnore($subscribers->map(fn ($s): array => [
                'campaign_id' => $campaign->id, 'subscriber_id' => $s->id, 'status' => 'queued', 'created_at' => $now, 'updated_at' => $now,
            ])->all());
        }, 'newsletter_subscribers.id', 'id');
        $campaign->forceFill(['send_status' => 'sending'])->save();
        SendCampaignBatch::dispatch($campaign->id);

        return $campaign->deliveries()->count();
    }

    /** Sends up to "per_minute" queued deliveries; true when more are left. */
    public function batch(Campaign $campaign): bool
    {
        $limit = max(1, (int) settings('newsletter.per_minute', 60));
        $batch = $campaign->deliveries()->where('status', 'queued')->with('subscriber')->orderBy('id')->limit($limit)->get();
        foreach ($batch as $delivery) {
            $subscriber = $delivery->subscriber;
            if ($subscriber === null || $subscriber->status !== 'subscribed') {   // left in the meantime (BRL-NLT-02)
                $delivery->update(['status' => 'skipped']);

                continue;
            }
            try {
                Mail::to($subscriber->email, $subscriber->name)->send(new CampaignMail($campaign, $subscriber));
                $delivery->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);
            } catch (Throwable $e) {
                $delivery->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500)]);
            }
        }
        $more = $campaign->deliveries()->where('status', 'queued')->exists();
        $counts = $campaign->deliveries()->select('status', DB::raw('COUNT(*) AS total'))->groupBy('status')->pluck('total', 'status');
        $campaign->forceFill([
            'sent_count' => (int) ($counts['sent'] ?? 0),
            'failed_count' => (int) ($counts['failed'] ?? 0),
            'bounce_count' => (int) ($counts['bounced'] ?? 0),
            ...($more ? [] : ['send_status' => ($counts['sent'] ?? 0) === 0 && ($counts['failed'] ?? 0) > 0 ? 'failed' : 'sent', 'sent_at' => now()]),
        ])->save();

        return $more;
    }

    public function test(Campaign $campaign, string $email): void
    {
        Mail::to($email)->send(new CampaignMail($campaign, null, test: true));
    }
}
