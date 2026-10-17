<?php

namespace Modules\Newsletter\Filament\Resources\Campaigns\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Newsletter\Filament\Resources\Campaigns\CampaignResource;

class ListCampaigns extends ListRecords
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
