{{--
    Alumni information form (FR-ALUM-07). Contract: wire:model name, student_id, batch, passing_year, current_designation,
    organization, email, phone, linkedin, story, website (honeypot); wire:submit submit; $done
--}}
<div class="alumni-submit">
    @if ($done)
        <p role="status" class="alumni-submit__done">{{ __('alumni::alumni.submit.thanks') }}</p>
    @else
        <form wire:submit="submit" style="display:grid;gap:var(--space-3, 12px)">
            @foreach ([
                'name' => ['text', true], 'email' => ['email', true], 'phone' => ['tel', false], 'student_id' => ['text', false],
                'batch' => ['text', false], 'passing_year' => ['number', false], 'current_designation' => ['text', false],
                'organization' => ['text', false], 'linkedin' => ['url', false],
            ] as $field => [$type, $required])
                <label style="display:grid;gap:4px">
                    <span>{{ __("alumni::alumni.fields.{$field}") }}@if ($required) *@endif</span>
                    <input type="{{ $type }}" wire:model="{{ $field }}" @required($required) style="padding:var(--space-3, 12px);border:1px solid var(--color-border);border-radius:var(--radius-sm, 4px)">
                    @error($field) <span class="form-error" role="alert">{{ $message }}</span> @enderror
                </label>
            @endforeach
            <label style="display:grid;gap:4px">
                <span>{{ __('alumni::alumni.fields.story') }}</span>
                <textarea wire:model="story" rows="5" maxlength="3000" style="padding:var(--space-3, 12px);border:1px solid var(--color-border);border-radius:var(--radius-sm, 4px)"></textarea>
                @error('story') <span class="form-error" role="alert">{{ $message }}</span> @enderror
            </label>
            <input type="text" wire:model="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px">
            <p style="color:var(--color-muted, #6b7280);font-size:.9em">{{ __('alumni::alumni.submit.privacy') }}</p>
            <button type="submit" class="button button-primary" wire:loading.attr="disabled">{{ __('alumni::alumni.submit.button') }}</button>
        </form>
    @endif
</div>
