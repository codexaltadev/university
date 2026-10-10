<?php

namespace Modules\Department\Filament\Resources\Departments\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Department\Filament\Resources\Departments\DepartmentResource;
use Modules\Department\Filament\Resources\Departments\Schemas\DepartmentForm;
use Modules\Department\Models\Department;
use Platform\Filament\Hub\MicrositeActions;

class EditDepartment extends EditRecord
{
    protected static string $resource = DepartmentResource::class;

    /** "Profile" in the Department Hub. */
    public static function getNavigationLabel(): string
    {
        return __('platform::hub.profile');
    }

    protected function getHeaderActions(): array
    {
        return [
            MicrositeActions::group(),   // Menus, Header & Footer, Homepage, Statistics, Pages of this microsite; view it
            DeleteAction::make(),
        ];
    }

    /** A scoped user cannot move the Department or change its name, short name or activity, whatever the request says. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (DepartmentForm::profileOnly()) {
            unset($data['faculty_id'], $data['name'], $data['short_name'], $data['is_active']);
        }

        return $data;
    }
}
