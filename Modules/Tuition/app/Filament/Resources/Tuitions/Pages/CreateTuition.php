<?php

namespace Modules\Tuition\Filament\Resources\Tuitions\Pages;

use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\Locked;
use Modules\Program\Models\Program;
use Modules\Tuition\Filament\Hub\ProgramTuitions;
use Modules\Tuition\Filament\Resources\Tuitions\TuitionResource;

class CreateTuition extends CreateRecord
{
    protected static string $resource = TuitionResource::class;

    /** Opened from a Program Hub (?program=12): that Program, fixed; back to the Hub after saving. */
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
        return $this->hubProgram
            ? ProgramTuitions::getUrl(['record' => $this->hubProgram])
            : parent::getRedirectUrl();
    }
}
