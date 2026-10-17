<?php

namespace Modules\Accreditation\Filament\Resources\Accreditations\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Accreditation\Filament\Resources\Accreditations\AccreditationResource;

class CreateAccreditation extends CreateRecord
{
    protected static string $resource = AccreditationResource::class;
}
