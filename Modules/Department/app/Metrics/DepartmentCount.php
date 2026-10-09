<?php

namespace Modules\Department\Metrics;

use Modules\Department\Models\Department;
use Platform\Statistics\MetricProvider;
use Platform\Statistics\StatContext;

/** Visible departments (C-20): the main site counts them all; a Faculty counts its own (AC-FAC-02). */
class DepartmentCount implements MetricProvider
{
    public function key(): string
    {
        return 'department.count';
    }

    public function label(): string
    {
        return __('department::department.metric_count');
    }

    public function count(StatContext $ctx, array $params): int
    {
        $ids = $ctx->idsOf((new Department)->getMorphClass());

        return Department::query()->frontendVisible()->when($ids !== null, fn ($q) => $q->whereKey($ids))->count();
    }
}
