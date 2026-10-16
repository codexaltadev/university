<?php

namespace Modules\Office\Metrics;

use Modules\Office\Models\Office;
use Modules\Office\Models\OfficeService;
use Platform\Assignments\Assignment;
use Platform\Statistics\MetricProvider;
use Platform\Statistics\StatContext;

/**
 * An office's automatic counters (FR-OFF-14): its active services ("office.services") and its
 * public team ("office.team"); on the main site, all offices together.
 */
abstract class OfficePartCount implements MetricProvider
{
    /** "services" or "team" */
    abstract protected function part(): string;

    public function key(): string
    {
        return 'office.'.$this->part();
    }

    public function label(): string
    {
        return __('office::office.metrics.'.$this->part());
    }

    public function count(StatContext $ctx, array $params): int
    {
        $ids = $ctx->idsOf((new Office)->getMorphClass());

        if ($this->part() === 'services') {
            return OfficeService::query()->where('is_active', true)->when($ids !== null, fn ($q) => $q->whereIn('office_id', $ids))->count();
        }

        return Assignment::query()->where('assignable_type', (new Office)->getMorphClass())->where('is_public', true)
            ->when($ids !== null, fn ($q) => $q->whereIn('assignable_id', $ids))
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', today()))
            ->distinct()->count('person_id');
    }
}
