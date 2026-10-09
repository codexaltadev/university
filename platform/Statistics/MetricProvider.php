<?php

namespace Platform\Statistics;

/**
 * A number a module can count for any context (FR-STAT-03, SAD Part B §7.2), registered
 * with $r->metric('program.count', ProgramCount::class). Count only frontend-visible
 * records (BRL-STAT-02) of the context and what is below it.
 */
interface MetricProvider
{
    public function key(): string;

    public function label(): string;

    /** @param array<string, mixed> $params e.g. ['level' => 'graduate'] */
    public function count(StatContext $ctx, array $params): int;
}
