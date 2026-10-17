<?php

namespace Modules\Lab\Metrics;

use Illuminate\Support\Facades\DB;
use Modules\Lab\Models\Lab;
use Modules\Lab\Models\LabItem;
use Platform\Statistics\MetricProvider;
use Platform\Statistics\StatContext;

/**
 * A lab's automatic counters (FR-LAB-11): its active equipment (by quantity), projects and products;
 * on the main site, all labs together.
 */
abstract class LabPartCount implements MetricProvider
{
    /** equipment / project / product */
    abstract protected function type(): string;

    public function key(): string
    {
        return 'lab.'.LabItem::SEGMENTS[$this->type()];
    }

    public function label(): string
    {
        return __('lab::lab.types.'.$this->type());
    }

    public function count(StatContext $ctx, array $params): int
    {
        $ids = $ctx->idsOf((new Lab)->getMorphClass());
        $query = LabItem::query()->ofType($this->type())->where('is_active', true)
            ->whereHas('lab', fn ($q) => $q->frontendVisible())
            ->when($ids !== null, fn ($q) => $q->whereIn('lab_id', $ids));

        return $this->type() === 'equipment' ? (int) $query->sum(DB::raw('COALESCE(quantity, 1)')) : $query->count();
    }
}
