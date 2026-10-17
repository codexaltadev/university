<?php

namespace Modules\Partners\Filament\Resources\Partners\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Partners\Filament\Resources\Partners\PartnerResource;

class CreatePartner extends CreateRecord
{
    protected static string $resource = PartnerResource::class;
}
