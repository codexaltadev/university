<?php

namespace Modules\Department\Filament\Resources\Departments\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Modules\Department\Filament\Resources\Departments\DepartmentResource;
use Modules\Department\Filament\Resources\Departments\Schemas\DepartmentForm;
use Modules\Department\Models\Department;

class EditDepartment extends EditRecord
{
    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')->label(__('department::department.actions.view_site'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(fn (Department $record): ?string => $record->url())->openUrlInNewTab(),
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
