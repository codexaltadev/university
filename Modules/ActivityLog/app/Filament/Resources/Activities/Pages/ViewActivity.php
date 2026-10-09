<?php

namespace Modules\ActivityLog\Filament\Resources\Activities\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\ActivityLog\Filament\Resources\Activities\ActivityResource;

class ViewActivity extends ViewRecord
{
    protected static string $resource = ActivityResource::class;
}
