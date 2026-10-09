<?php

namespace Modules\Faq\Metrics;

use Modules\Faq\Models\Faq;
use Platform\Statistics\MetricProvider;
use Platform\Statistics\StatContext;

/** Visible FAQs of a context and what is below it (Module Guide §11.2). */
class FaqCount implements MetricProvider
{
    public function key(): string
    {
        return 'faq.count';
    }

    public function label(): string
    {
        return __('faq::faq.metric_count');
    }

    public function count(StatContext $ctx, array $params): int
    {
        return Faq::query()->frontendVisible()->inContext($ctx->asSiteContext(), descendants: true)->count();
    }
}
