{{-- Newsletter sign-up (FR-NLT-01). Contract: wire:model email, name, lists, website (honeypot); wire:submit subscribe; $done, $compact, $publicLists --}}
<div class="newsletter-subscribe">
    @if ($done)
        <p class="newsletter-subscribe__done" role="status">{{ $done }}</p>
    @else
        <form wire:submit="subscribe" style="display:grid;gap:var(--space-3, 12px)">
            @if (! $compact)
                <input type="text" wire:model="name" maxlength="191" aria-label="{{ __('newsletter::newsletter.fields.name') }}" placeholder="{{ __('newsletter::newsletter.form.name_placeholder') }}"
                       style="padding:var(--space-3, 12px);border:1px solid var(--color-border);border-radius:var(--radius-sm, 4px)">
            @endif
            <div style="display:flex;flex-wrap:wrap;gap:var(--space-2, 8px)">
                <input type="email" wire:model="email" required maxlength="191" aria-label="{{ __('newsletter::newsletter.fields.email') }}" placeholder="{{ __('newsletter::newsletter.form.email_placeholder') }}"
                       style="flex:1 1 200px;padding:var(--space-3, 12px);border:1px solid var(--color-border);border-radius:var(--radius-sm, 4px)">
                <input type="text" wire:model="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px">
                <button type="submit" class="button button-primary" wire:loading.attr="disabled">{{ __('newsletter::newsletter.form.button') }}</button>
            </div>
            @if ($publicLists->count() > 1)
                <fieldset style="border:0;padding:0;margin:0;display:flex;flex-wrap:wrap;gap:var(--space-3, 12px)">
                    <legend style="margin-bottom:4px">{{ __('newsletter::newsletter.form.lists') }}</legend>
                    @foreach ($publicLists as $list)
                        <label title="{{ $list->description }}"><input type="checkbox" wire:model="lists" value="{{ $list->id }}"> {{ $list->name }}</label>
                    @endforeach
                </fieldset>
            @endif
            @error('email') <p class="form-error" role="alert">{{ $message }}</p> @enderror
            <p style="margin:0;font-size:.8rem;opacity:.75">{{ __('newsletter::newsletter.form.privacy') }}</p>
        </form>
    @endif
</div>
