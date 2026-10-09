<?php

namespace Modules\Faculty\Filament\Resources\Faculties\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Faculty\Filament\Resources\Faculties\FacultyResource;

class CreateFaculty extends CreateRecord
{
    protected static string $resource = FacultyResource::class;
}
