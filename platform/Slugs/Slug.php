<?php

namespace Platform\Slugs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** One slug of a record in one language (core_slugs, P-21, DB-01 §9). */
class Slug extends Model
{
    protected $table = 'core_slugs';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_current' => 'boolean'];
    }

    public function sluggable(): MorphTo
    {
        return $this->morphTo();
    }
}
