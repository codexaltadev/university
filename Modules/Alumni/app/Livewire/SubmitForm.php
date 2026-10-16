<?php

namespace Modules\Alumni\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Modules\Alumni\Models\Alumnus;

/**
 * "Share your information" (FR-ALUM-07, BRL-ALUM-02): a graduate fills in who they are and what
 * they do; it is saved switched off with source "public_form" until the Alumni Office checks it
 * (AC-ALUM-02). Contacts stay hidden. 3 a minute per visitor and a hidden trap field.
 * View: modules/alumni/livewire/submit-form.
 */
class SubmitForm extends Component
{
    public string $name = '';

    public string $student_id = '';

    public string $batch = '';

    public string $passing_year = '';

    public string $current_designation = '';

    public string $organization = '';

    public string $email = '';

    public string $phone = '';

    public string $linkedin = '';

    public string $story = '';

    /** Honeypot */
    public string $website = '';

    public bool $done = false;

    public function submit(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'student_id' => ['nullable', 'string', 'max:30'],
            'batch' => ['nullable', 'string', 'max:30'],
            'passing_year' => ['nullable', 'integer', 'min:1990', 'max:'.(date('Y') + 1)],
            'current_designation' => ['nullable', 'string', 'max:191'],
            'organization' => ['nullable', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191'],
            'phone' => ['nullable', 'regex:/^\+?[0-9 ()-]{8,20}$/'],
            'linkedin' => ['nullable', 'url', 'max:255'],
            'story' => ['nullable', 'string', 'max:3000'],
        ], [], collect(['name', 'student_id', 'batch', 'passing_year', 'current_designation', 'organization', 'email', 'phone', 'linkedin', 'story'])
            ->mapWithKeys(fn (string $f): array => [$f => __("alumni::alumni.fields.{$f}")])->all());

        $key = 'alumni-submit:'.request()->ip();
        if ($this->website !== '' || RateLimiter::tooManyAttempts($key, 3)) {
            $this->done = true;   // say nothing more to a bot

            return;
        }
        RateLimiter::hit($key, 60);

        $studentId = trim($this->student_id) ?: null;
        if ($studentId && Alumnus::query()->withoutGlobalScopes()->whereNull('deleted_at')->where('student_id', $studentId)->exists()) {
            $studentId = null;   // already listed: the office matches the submission by hand
        }

        Alumnus::query()->create([
            'name' => ['en' => trim($this->name)],
            'student_id' => $studentId,
            'batch' => trim($this->batch) ?: null,
            'passing_year' => $this->passing_year !== '' ? (int) $this->passing_year : null,
            'current_designation' => filled($this->current_designation) ? ['en' => trim($this->current_designation)] : null,
            'organization' => filled($this->organization) ? ['en' => trim($this->organization)] : null,
            'email' => trim($this->email),
            'phone' => trim($this->phone) ?: null,
            'social_links' => filled($this->linkedin) ? ['linkedin' => $this->linkedin] : null,
            'story' => filled($this->story) ? ['en' => '<p>'.nl2br(e(trim($this->story))).'</p>'] : null,
            'submission_source' => 'public_form',
            'is_active' => false,
        ]);

        $this->reset(['name', 'student_id', 'batch', 'passing_year', 'current_designation', 'organization', 'email', 'phone', 'linkedin', 'story']);
        $this->done = true;
    }

    public function render(): View
    {
        return view('alumni::livewire.submit-form');
    }
}
