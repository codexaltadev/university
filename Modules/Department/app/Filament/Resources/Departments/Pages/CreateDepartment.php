<?php

namespace Modules\Department\Filament\Resources\Departments\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Department\Filament\Resources\Departments\DepartmentResource;

class CreateDepartment extends CreateRecord
{
    protected static string $resource = DepartmentResource::class;
}
