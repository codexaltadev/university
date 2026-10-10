<?php

namespace Modules\Program\Filament\Resources\Programs\Pages;

use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\Locked;
use Modules\Department\Models\Department;
use Modules\Program\Filament\Hub\DepartmentPrograms;
use Modules\Program\Filament\Resources\Programs\ProgramResource;

class CreateProgram extends CreateRecord
{
    protected static string $resource = ProgramResource::class;

    /** Opened from a Department Hub (?department=12): that Department, fixed; back to the Hub after saving. */
    #[Locked]
    public ?int $hubDepartment = null;

    public function mount(): void
    {
        if ($department = request()->integer('department')) {
            // Only a Department the user may see (the scope filter applies): another one answers 404
            $this->hubDepartment = Department::query()->findOrFail($department)->getKey();
        }

        parent::mount();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($this->hubDepartment) {
            $data['department_id'] = $this->hubDepartment;   // whatever the request says
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->hubDepartment
            ? DepartmentPrograms::getUrl(['record' => $this->hubDepartment])
            : parent::getRedirectUrl();
    }
}
