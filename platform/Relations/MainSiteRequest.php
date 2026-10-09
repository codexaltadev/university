<?php

namespace Platform\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * An entity user's request to show content on the main site (core_main_site_requests,
 * P-23, FR-CNT-H09). The decision is the Admin's. Log table.
 */
class MainSiteRequest extends Model
{
    public const PENDING = 'pending';

    public const PUBLISHED = 'published';

    public const DISMISSED = 'dismissed';

    public $timestamps = false;

    protected $table = 'core_main_site_requests';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['requested_at' => 'datetime', 'decided_at' => 'datetime'];
    }

    public function content(): MorphTo
    {
        return $this->morphTo();
    }
}
