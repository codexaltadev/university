<?php

namespace Platform\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/** A content's "Show in" targets or main-site state changed (listing / statistics cache). */
class ContentRelationsChanged
{
    use Dispatchable;

    public function __construct(public readonly Model $content) {}
}
