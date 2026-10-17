<?php

namespace Modules\International\Metrics;

use Modules\International\Support\InternationalWebsite;
use Platform\Statistics\MetricProvider;
use Platform\Statistics\StatContext;

/** Statistics counter "Countries" (FR-INTL-08/09): the countries of the place's partner institutions. */
class CountriesCount implements MetricProvider
{
    public function key(): string
    {
        return 'international.countries';
    }

    public function label(): string
    {
        return __('international::international.countries');
    }

    public function count(StatContext $ctx, array $params): int
    {
        return InternationalWebsite::institutions(PartnersCount::ownerKey($ctx))->reorder()->distinct()->count('country_id');
    }
}
