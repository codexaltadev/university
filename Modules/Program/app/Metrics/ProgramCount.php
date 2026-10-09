<?php

namespace Modules\Program\Metrics;

use Modules\Program\Models\Program;
use Platform\Statistics\MetricProvider;
use Platform\Statistics\StatContext;

/** Visible programs (C-20): the main site counts them all; a Faculty or Department counts its own. */
class ProgramCount implements MetricProvider
{
    public function key(): string
    {
        return 'program.count';
    }

    public function label(): string
    {
        return __('program::program.metric_count');
    }

    public function count(StatContext $ctx, array $params): int
    {
        $ids = $ctx->idsOf((new Program)->getMorphClass());

        return Program::query()->frontendVisible()->when($ids !== null, fn ($q) => $q->whereKey($ids))->count();
    }
}
