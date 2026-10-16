<?php

namespace Modules\Achievements\Metrics;

use Modules\Achievements\Models\Achievement;
use Platform\Statistics\MetricProvider;
use Platform\Statistics\StatContext;

/**
 * Statistics counter "Achievements" (FR-ACH-03): the visible achievements of a place and those
 * below it; params ['level' => 'international'] for "100+ International Awards".
 */
class AchievementsCount implements MetricProvider
{
    public function key(): string
    {
        return 'achievements.count';
    }

    public function label(): string
    {
        return __('achievements::achievements.plural');
    }

    public function count(StatContext $ctx, array $params): int
    {
        $level = in_array($params['level'] ?? null, Achievement::LEVELS, true) ? $params['level'] : null;

        return Achievement::query()->frontendVisible()->inContext($ctx->asSiteContext(), descendants: true)->ofLevel($level)->count();
    }
}
