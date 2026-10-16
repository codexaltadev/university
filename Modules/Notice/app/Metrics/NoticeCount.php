<?php

namespace Modules\Notice\Metrics;

use Modules\Notice\Models\Notice;
use Platform\Statistics\MetricProvider;
use Platform\Statistics\StatContext;

/** Statistics counter "Notices": the visible notices of a place and those below it. */
class NoticeCount implements MetricProvider
{
    public function key(): string
    {
        return 'notice.count';
    }

    public function label(): string
    {
        return __('notice::notice.plural');
    }

    public function count(StatContext $ctx, array $params): int
    {
        return Notice::query()->frontendVisible()->inContext($ctx->asSiteContext(), descendants: true)->count();
    }
}
