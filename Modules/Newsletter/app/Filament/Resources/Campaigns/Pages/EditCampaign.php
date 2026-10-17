<?php

namespace Modules\Newsletter\Filament\Resources\Campaigns\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Newsletter\Filament\Resources\Campaigns\CampaignResource;

class EditCampaign extends EditRecord
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
