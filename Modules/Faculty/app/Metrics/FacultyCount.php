<?php

namespace Modules\Faculty\Metrics;

use Modules\Faculty\Models\Faculty;
use Platform\Statistics\MetricProvider;
use Platform\Statistics\StatContext;

/** Visible faculties (C-20): the main site counts them all; a Faculty counts itself. */
class FacultyCount implements MetricProvider
{
    public function key(): string
    {
        return 'faculty.count';
    }

    public function label(): string
    {
        return __('faculty::faculty.metric_count');
    }

    public function count(StatContext $ctx, array $params): int
    {
        $ids = $ctx->idsOf((new Faculty)->getMorphClass());

        return Faculty::query()->frontendVisible()->when($ids !== null, fn ($q) => $q->whereKey($ids))->count();
    }
}
