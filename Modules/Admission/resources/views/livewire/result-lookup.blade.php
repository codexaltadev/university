{{-- Admission result lookup (FR-ADM-06). Contract: wire:model serial, website (honeypot); wire:submit lookup; $answer (status, name, label, message, batch) --}}
<div class="admission-result" x-data>
    <form wire:submit="lookup" class="admission-result__form" style="display:flex;flex-wrap:wrap;gap:var(--space-3, 12px);align-items:flex-end">
        <label style="flex:1 1 260px;display:grid;gap:var(--space-1, 4px)">
            <span>{{ __('admission::admission.fields.serial') }}</span>
            <input type="text" wire:model="serial" required maxlength="50" autocomplete="off" inputmode="text"
                   placeholder="{{ __('admission::admission.result_page.placeholder') }}"
                   style="padding:var(--space-3, 12px);border:1px solid var(--color-border);border-radius:var(--radius-sm, 4px)">
        </label>
        <input type="text" wire:model="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px">
        <button type="submit" class="button button-primary" wire:loading.attr="disabled">{{ __('admission::admission.result_page.search') }}</button>
    </form>
    @error('serial') <p class="form-error" role="alert">{{ $message }}</p> @enderror

    <div aria-live="polite">
        @if ($answer)
            <div class="admission-result__answer admission-result__answer--{{ $answer['status'] }}{{ isset($answer['result']) ? ' admission-result__answer--'.$answer['result'] : '' }}"
                 style="margin-top:var(--space-6, 24px);padding:var(--space-6, 24px);border:1px solid var(--color-border);border-radius:var(--radius-md, 8px);background:var(--color-surface)">
                @if ($answer['status'] === 'found')
                    <p style="margin:0;color:var(--color-text-muted)">{{ $answer['batch'] }}</p>
                    <h2 style="margin:var(--space-2, 8px) 0">{{ $answer['name'] }}</h2>
                    <p><strong>{{ __('admission::admission.fields.result') }}:</strong> {{ $answer['label'] }}</p>
                @endif
                @if (filled($answer['message'] ?? null))
                    <div style="white-space:pre-line">{{ $answer['message'] }}</div>
                @endif
            </div>
        @endif
    </div>
</div>
