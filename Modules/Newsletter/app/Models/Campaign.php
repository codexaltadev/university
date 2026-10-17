<?php

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Newsletter\Database\Factories\CampaignFactory;
use Platform\Models\BaseModel;

/**
 * A campaign (M-39, DB-06 v1.3 §10, FR-NLT-05/06): subject and body in one language, to some
 * lists, now or at a time; draft → scheduled → sending → sent (or failed); counts for the report.
 */
class Campaign extends BaseModel
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory;

    public const STATUSES = ['draft', 'scheduled', 'sending', 'sent', 'failed'];

    public static string $titleAttribute = 'subject';

    protected $table = 'newsletter_campaigns';

    protected $fillable = ['subject', 'locale', 'body', 'source_url', 'source_label', 'scheduled_at', 'is_active'];

    protected $attributes = ['send_status' => 'draft', 'locale' => 'en'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'sent_at' => 'datetime', 'sent_count' => 'integer', 'failed_count' => 'integer', 'bounce_count' => 'integer'];
    }

    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(MailingList::class, 'newsletter_campaign_lists', 'campaign_id', 'list_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'campaign_id');
    }

    public function isEditable(): bool
    {
        return in_array($this->send_status, ['draft', 'scheduled'], true);
    }

    /** The reachable subscribers of its lists, each once (BRL-NLT-01/02). */
    public function audience(): Builder
    {
        $lists = $this->lists()->pluck('newsletter_lists.id');

        return Subscriber::query()->reachable()->whereHas('lists', fn (Builder $q) => $q->whereIn('newsletter_lists.id', $lists)->whereNull('newsletter_list_subscriber.unsubscribed_at'));
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->where('send_status', 'scheduled')->where(fn (Builder $q) => $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now()));
    }

    protected static function newFactory(): CampaignFactory
    {
        return CampaignFactory::new();
    }
}
