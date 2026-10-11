<?php

namespace Modules\Admission\Filament\Resources\Steps\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\Admission\Filament\Resources\Steps\AdmissionStepResource;

class ManageAdmissionSteps extends ManageRecords
{
    protected static string $resource = AdmissionStepResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
