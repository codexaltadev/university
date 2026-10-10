<?php

namespace Modules\Tuition\Filament\Resources\Tuitions\Pages;

use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\Locked;
use Modules\Program\Filament\Resources\Programs\ProgramResource;
use Modules\Program\Models\Program;
use Modules\Tuition\Filament\Resources\Tuitions\TuitionResource;

class CreateTuition extends CreateRecord
{
    protected static string $resource = TuitionResource::class;

    /** Opened from the Program editor (?program=12): that Program, fixed; back to the program after saving. */
    #[Locked]
    public ?int $hubProgram = null;

    public function mount(): void
    {
        if ($program = request()->integer('program')) {
            // Only a Program the user may see (the scope filter applies): another one answers 404
            $this->hubProgram = Program::query()->findOrFail($program)->getKey();
        }

        parent::mount();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($this->hubProgram) {
            $data['program_id'] = $this->hubProgram;   // whatever the request says
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        if ($this->hubProgram === null) {
            return parent::getRedirectUrl();
        }

        $program = Program::query()->find($this->hubProgram);

        return ProgramResource::getUrl(auth()->user()?->can('update', $program) ? 'edit' : 'view', ['record' => $this->hubProgram]);
    }
}
