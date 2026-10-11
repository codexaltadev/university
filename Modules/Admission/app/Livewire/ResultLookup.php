<?php

namespace Modules\Admission\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Modules\Admission\Models\AdmissionResult;
use Modules\Admission\Models\ResultBatch;

/**
 * Admission result lookup (FR-ADM-06/07, AC-ADM-01/02): the applicant types the application
 * serial and sees their name and result with the batch's message — nothing else (BRL-ADM-02).
 * Published batches only, the newest first. Limited per visitor (10 a minute) with a hidden
 * trap field against bots. View: theme-overridable modules/admission/livewire/result-lookup.
 */
class ResultLookup extends Component
{
    public const PER_MINUTE = 10;

    public string $serial = '';

    /** Honeypot: people never fill it. */
    public string $website = '';

    /** @var array{status: string, name?: string, result?: string, label?: string, message?: ?string, batch?: string}|null */
    public ?array $answer = null;

    public function lookup(): void
    {
        $this->validate(['serial' => ['required', 'string', 'max:50']], [], ['serial' => __('admission::admission.fields.serial')]);

        if ($this->website !== '') {
            $this->answer = ['status' => 'not_found', 'message' => null];

            return;
        }

        $key = 'admission-result:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, self::PER_MINUTE)) {
            $this->answer = ['status' => 'too_many', 'message' => __('admission::admission.result_page.too_many', ['seconds' => RateLimiter::availableIn($key)])];

            return;
        }
        RateLimiter::hit($key, 60);

        $this->answer = $this->find(trim($this->serial));
    }

    /** @return array<string, mixed> */
    public function find(string $serial): array
    {
        $batches = ResultBatch::query()->frontendVisible()->latest('id')->get();
        if ($batches->isEmpty()) {
            return ['status' => 'unpublished', 'message' => __('admission::admission.result_page.unpublished')];   // AC-ADM-02
        }

        $result = AdmissionResult::query()->where('is_active', true)
            ->whereIn('batch_id', $batches->pluck('id'))->where('application_serial', $serial)
            ->orderByDesc('batch_id')->first();

        if ($result === null) {
            return ['status' => 'not_found', 'message' => $batches->first()->message('not_found') ?? __('admission::admission.result_page.not_found')];
        }

        $batch = $batches->firstWhere('id', $result->batch_id);

        return [
            'status' => 'found',
            'name' => $result->student_name,
            'result' => $result->result,
            'label' => $result->result_label ?: __("admission::admission.results.{$result->result}"),
            'message' => $batch->message($result->result, $result->student_name),
            'batch' => $batch->name,
        ];
    }

    public function render(): View
    {
        return view('admission::livewire.result-lookup');
    }
}
