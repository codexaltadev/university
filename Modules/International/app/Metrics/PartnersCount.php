<?php

namespace Modules\International\Metrics;

use Modules\International\Support\InternationalWebsite;
use Platform\Relations\RelationEngine;
use Platform\Statistics\MetricProvider;
use Platform\Statistics\StatContext;

/** Statistics counter "International partners" (FR-INTL-08): the place's partner institutions. */
class PartnersCount implements MetricProvider
{
    public function key(): string
    {
        return 'international.partners';
    }

    public function label(): string
    {
        return __('international::international.partners');
    }

    public function count(StatContext $ctx, array $params): int
    {
        return InternationalWebsite::institutions(self::ownerKey($ctx))->count();
    }

    public static function ownerKey(StatContext $ctx): string
    {
        $site = $ctx->asSiteContext();

        return $site->isMicrosite() ? $site->key() : RelationEngine::SITE_KEY;
    }
}
