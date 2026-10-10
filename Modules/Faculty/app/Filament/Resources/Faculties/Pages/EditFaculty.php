<?php

namespace Modules\Faculty\Filament\Resources\Faculties\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Faculty\Filament\Resources\Faculties\FacultyResource;
use Modules\Faculty\Filament\Resources\Faculties\Schemas\FacultyForm;
use Modules\Faculty\Models\Faculty;
use Platform\Filament\Hub\MicrositeActions;

class EditFaculty extends EditRecord
{
    protected static string $resource = FacultyResource::class;

    /** "Profile" in the Faculty Hub. */
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

    /** BRL-FAC-02: a scoped user cannot change the name, short name or activity, whatever the request says. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (FacultyForm::profileOnly()) {
            unset($data['name'], $data['short_name'], $data['is_active']);
        }

        return $data;
    }
}
