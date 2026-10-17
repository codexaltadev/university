<?php

namespace Modules\Partners\Filament\Resources\Partners\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Partners\Filament\Resources\Partners\PartnerResource;

class EditPartner extends EditRecord
{
    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
