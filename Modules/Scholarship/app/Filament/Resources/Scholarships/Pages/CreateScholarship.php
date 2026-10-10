<?php

namespace Modules\Scholarship\Filament\Resources\Scholarships\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Scholarship\Filament\Resources\Scholarships\ScholarshipResource;

/** ?parent=3 ("Add part" on a scholarship's row) presets where the new one belongs. */
class CreateScholarship extends CreateRecord
{
    protected static string $resource = ScholarshipResource::class;
}
