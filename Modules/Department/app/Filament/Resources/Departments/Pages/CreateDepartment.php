<?php

namespace Modules\Department\Filament\Resources\Departments\Pages;

use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\Locked;
use Modules\Department\Filament\Resources\Departments\DepartmentResource;
use Modules\Faculty\Filament\Resources\Faculties\FacultyResource;
use Modules\Faculty\Models\Faculty;

class CreateDepartment extends CreateRecord
{
    protected static string $resource = DepartmentResource::class;

    /** Opened from a Faculty Hub (?faculty=12): that Faculty, fixed; back to the Faculty after saving. */
    #[Locked]
    public ?int $hubFaculty = null;

    public function mount(): void
    {
        if ($faculty = request()->integer('faculty')) {
            // Only a Faculty the user may see (the scope filter applies): another one answers 404
            $this->hubFaculty = Faculty::query()->findOrFail($faculty)->getKey();
        }

        parent::mount();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($this->hubFaculty) {
            $data['faculty_id'] = $this->hubFaculty;   // whatever the request says
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->hubFaculty && auth()->user()?->can('update', Faculty::query()->find($this->hubFaculty))
            ? FacultyResource::getUrl('edit', ['record' => $this->hubFaculty])
            : parent::getRedirectUrl();
    }
}
