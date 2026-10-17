<?php

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Models\LogModel;

/** One campaign to one subscriber (DB-06 v1.3 §10, FR-NLT-06): queued / sent / failed / bounced; removed after 90 days, the campaign keeps its counts. */
class Delivery extends LogModel
{
    use MassPrunable;

    protected $table = 'newsletter_deliveries';

    protected $fillable = ['campaign_id', 'subscriber_id', 'status', 'error', 'sent_at'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class, 'subscriber_id');
    }

    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays(90));
    }
}
