<?php

namespace Modules\Office\Metrics;

use Modules\Office\Models\Office;
use Platform\Statistics\MetricProvider;
use Platform\Statistics\StatContext;

/** Visible offices (SRS C-20 initial metrics); offices have no parent, so the main site counts them all. */
class OfficeCount implements MetricProvider
{
    public function key(): string
    {
        return 'office.count';
    }

    public function label(): string
    {
        return __('office::office.metric_count');
    }

    public function count(StatContext $ctx, array $params): int
    {
        $ids = $ctx->idsOf((new Office)->getMorphClass());

        return Office::query()->frontendVisible()->when($ids !== null, fn ($q) => $q->whereKey($ids))->count();
    }
}
