<?php

namespace Modules\Fee\Filament\Resources\Tuitions\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Fee\Filament\Resources\Tuitions\TuitionResource;

class CreateTuition extends CreateRecord
{
    protected static string $resource = TuitionResource::class;
}
