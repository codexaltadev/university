<?php

namespace Modules\Program\Metrics;

use Modules\Program\Models\Program;
use Modules\Program\Models\ProgramItem;
use Platform\Statistics\MetricProvider;
use Platform\Statistics\StatContext;

/**
 * Active sub-items of the visible programs in a context (FR-PROG-11): a Program counts
 * its own, a Department or Faculty those of its programs, the main site all.
 */
abstract class ProgramItemCount implements MetricProvider
{
    /** @return class-string<ProgramItem> */
    abstract protected function model(): string;

    public function count(StatContext $ctx, array $params): int
    {
        $ids = $ctx->idsOf((new Program)->getMorphClass());
        $model = $this->model();

        return $model::query()->frontendVisible()
            ->whereHas('program', fn ($q) => $q->frontendVisible()->when($ids !== null, fn ($p) => $p->whereKey($ids)))
            ->count();
    }
}
