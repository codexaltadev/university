<?php

namespace Platform\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/** Highlights changed or expired (homepage cache, SAD Part A §8). Content is null for the expiry job. */
class ContentHighlightChanged
{
    use Dispatchable;

    public function __construct(public readonly ?Model $content) {}
}
