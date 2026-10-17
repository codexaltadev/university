<?php

namespace Modules\Lab\Metrics;

use Modules\Lab\Models\Lab;
use Platform\Statistics\MetricProvider;
use Platform\Statistics\StatContext;

/** "lab.count": the visible labs of a place (a department's, or all on the main site). */
class LabCount implements MetricProvider
{
    public function key(): string
    {
        return 'lab.count';
    }

    public function label(): string
    {
        return __('lab::lab.plural');
    }

    public function count(StatContext $ctx, array $params): int
    {
        $departments = $ctx->idsOf('department.department');

        return Lab::query()->frontendVisible()->when($departments !== null, fn ($q) => $q->whereIn('department_id', $departments))->count();
    }
}
