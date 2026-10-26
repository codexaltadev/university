<?php

namespace Modules\Seo\Filament\Resources\SearchTerms\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Seo\Filament\Resources\SearchTerms\SearchTermResource;

class ListSearchTerms extends ListRecords
{
    protected static string $resource = SearchTermResource::class;
}
