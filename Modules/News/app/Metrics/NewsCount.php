<?php

namespace Modules\News\Metrics;

use Modules\News\Models\Post;
use Platform\Statistics\MetricProvider;
use Platform\Statistics\StatContext;

class NewsCount implements MetricProvider
{
    public function key(): string
    {
        return 'news.count';
    }

    public function label(): string
    {
        return __('news::news.metric_count');
    }

    public function count(StatContext $ctx, array $params): int
    {
        return Post::query()->frontendVisible()->inContext($ctx->asSiteContext(), descendants: true)->count();
    }
}
