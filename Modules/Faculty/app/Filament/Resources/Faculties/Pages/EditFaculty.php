<?php

namespace Modules\Faculty\Filament\Resources\Faculties\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Modules\Faculty\Filament\Resources\Faculties\FacultyResource;
use Modules\Faculty\Filament\Resources\Faculties\Schemas\FacultyForm;
use Modules\Faculty\Models\Faculty;

class EditFaculty extends EditRecord
{
    protected static string $resource = FacultyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')->label(__('faculty::faculty.actions.view_site'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(fn (Faculty $record): ?string => $record->url())->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }

    /** BRL-FAC-02: a scoped user cannot change the name, short name or activity, whatever the request says. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (FacultyForm::profileOnly()) {
            unset($data['name'], $data['short_name'], $data['is_active']);
        }

        return $data;
    }
}
