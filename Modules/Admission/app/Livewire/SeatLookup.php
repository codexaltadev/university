<?php

namespace Modules\Admission\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Modules\Admission\Models\AdmissionSeat;
use Modules\Admission\Support\AdmissionPage;

/**
 * Admission test seat lookup (FR-ADM-17, DIU's /seat-plan): the applicant types the application serial and sees their
 * name, program, building, room and floor — nothing else — with the buildings' pictures, theirs marked. Limited per
 * visitor (10 a minute) with a hidden trap field against bots. View: theme-overridable modules/admission/livewire/seat-lookup.
 */
class SeatLookup extends Component
{
    public string $serial = '';

    /** Honeypot: people never fill it. */
    public string $website = '';

    /** @var array{status: string, name?: string, serial?: string, program?: ?string, building?: string, room?: string, floor?: ?string}|null */
    public ?array $answer = null;

    public function lookup(): void
    {
        $this->validate(['serial' => ['required', 'string', 'max:50']], [], ['serial' => __('admission::admission.fields.serial')]);

        if ($this->website !== '') {
            $this->answer = ['status' => 'not_found'];

            return;
        }

        $key = 'admission-seat:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, ResultLookup::PER_MINUTE)) {
            $this->answer = ['status' => 'too_many', 'seconds' => RateLimiter::availableIn($key)];

            return;
        }
        RateLimiter::hit($key, 60);

        $this->answer = $this->find(trim($this->serial));
    }

    public function clear(): void
    {
        $this->reset('serial', 'answer');
    }

    /** @return array<string, mixed> */
    public function find(string $serial): array
    {
        $seat = AdmissionSeat::query()->where('is_active', true)->where('application_serial', $serial)->first();

        return $seat === null ? ['status' => 'not_found'] : [
            'status' => 'found',
            'name' => $seat->student_name,
            'serial' => $seat->application_serial,
            'program' => $seat->program_label ?: null,
            'building' => $seat->building,
            'room' => $seat->room,
            'floor' => $seat->floor ?: null,
        ];
    }

    public function render(): View
    {
        return view('admission::livewire.seat-lookup', [
            'buildings' => (new AdmissionPage)->seatBuildings($this->answer['building'] ?? null),
        ]);
    }
}
