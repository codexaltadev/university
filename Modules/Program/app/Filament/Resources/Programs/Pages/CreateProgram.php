<?php

namespace Modules\Program\Filament\Resources\Programs\Pages;

use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Livewire\Attributes\Locked;
use Modules\Department\Filament\Resources\Departments\DepartmentResource;
use Modules\Department\Models\Department;
use Modules\Program\Filament\Resources\Programs\ProgramResource;
use Modules\Program\Filament\Resources\Programs\Schemas\ProgramForm;

/** A new program, step by step (UI/UX Part A §5.2 v1.2): the editor's parts as a wizard; any step but the first may be skipped. */
class CreateProgram extends CreateRecord
{
    use HasWizard;

    protected static string $resource = ProgramResource::class;

    /** Opened from a Department editor (?department=12): that Department, fixed; back to the Department after saving. */
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

    public function getSteps(): array
    {
        return ProgramForm::steps();
    }

    protected function hasSkippableSteps(): bool
    {
        return true;
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
        return $this->hubDepartment && auth()->user()?->can('update', Department::query()->find($this->hubDepartment))
            ? DepartmentResource::getUrl('edit', ['record' => $this->hubDepartment])
            : ProgramResource::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
