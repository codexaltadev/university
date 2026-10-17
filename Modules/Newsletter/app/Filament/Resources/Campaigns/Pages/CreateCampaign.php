<?php

namespace Modules\Newsletter\Filament\Resources\Campaigns\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Newsletter\Filament\Resources\Campaigns\CampaignResource;

class CreateCampaign extends CreateRecord
{
    protected static string $resource = CampaignResource::class;
}
