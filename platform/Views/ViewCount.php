<?php

namespace Platform\Views;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** A record's view total (DB-02 §12.3, P-17). Written by ViewCounter::flush(), never per page view. */
class ViewCount extends Model
{
    protected $table = 'core_view_counts';

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'viewable_id';

    protected $fillable = ['viewable_type', 'viewable_id', 'total', 'last_viewed_at'];

    protected function casts(): array
    {
        return ['total' => 'integer', 'last_viewed_at' => 'datetime'];
    }

    public function viewable(): MorphTo
    {
        return $this->morphTo();
    }
}
