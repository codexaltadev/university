<?php

namespace Modules\Admission\Filament\Resources\ContactGroups\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Admission\Filament\Resources\ContactGroups\AdmissionContactGroupResource;

class CreateAdmissionContactGroup extends CreateRecord
{
    protected static string $resource = AdmissionContactGroupResource::class;
}
