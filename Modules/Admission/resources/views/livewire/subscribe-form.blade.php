{{-- Admission updates sign-up (FR-ADM-09). Contract: wire:model channel, contact, website (honeypot); wire:submit subscribe; $done --}}
<div class="admission-subscribe">
    @if ($done)
        <p class="admission-subscribe__done" role="status">{{ $done }}</p>
    @else
        <form wire:submit="subscribe" style="display:grid;gap:var(--space-3, 12px)">
            <div role="radiogroup" style="display:flex;gap:var(--space-4, 16px)">
                @foreach (\Modules\Admission\Models\AdmissionSubscriber::CHANNELS as $option)
                    <label><input type="radio" wire:model.live="channel" value="{{ $option }}"> {{ __("admission::admission.channels.{$option}") }}</label>
                @endforeach
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:var(--space-3, 12px)">
                <input type="{{ $channel === 'email' ? 'email' : 'tel' }}" wire:model="contact" required maxlength="191"
                       aria-label="{{ __('admission::admission.fields.contact') }}"
                       placeholder="{{ $channel === 'email' ? __('admission::admission.subscribe.email_placeholder') : __('admission::admission.subscribe.phone_placeholder') }}"
                       style="flex:1 1 220px;padding:var(--space-3, 12px);border:1px solid var(--color-border);border-radius:var(--radius-sm, 4px)">
                <input type="text" wire:model="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px">
                <button type="submit" class="btn btn--primary" wire:loading.attr="disabled">{{ __('admission::admission.subscribe.button') }}</button>
            </div>
            @error('contact') <p class="form-error" role="alert">{{ $message }}</p> @enderror
        </form>
    @endif
</div>
