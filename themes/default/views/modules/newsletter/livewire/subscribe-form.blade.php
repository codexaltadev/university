{{--
    Newsletter sign-up, default theme look (DIU footer: white field, "Subscribe" beside it).
    Contract (Newsletter module): wire:model email, name, lists, website (honeypot); wire:submit subscribe; $done, $compact, $publicLists
--}}
<div class="newsletter-subscribe">
    @if ($done)
        <p class="text-white/90" role="status">{{ $done }}</p>
    @else
        <form wire:submit="subscribe" class="space-y-3">
            @if (! $compact)
                <input type="text" wire:model="name" maxlength="191" aria-label="{{ __('newsletter::newsletter.fields.name') }}" placeholder="{{ __('newsletter::newsletter.form.name_placeholder') }}"
                       class="w-full !min-h-0 rounded-md px-6 py-2.5 bg-white text-neutral-800 placeholder-neutral-400 text-sm outline-none border-0">
            @endif
            <div class="flex gap-4">
                <input type="email" wire:model="email" required maxlength="191" aria-label="{{ __('newsletter::newsletter.fields.email') }}" placeholder="{{ __('theme::theme.email_placeholder') }}"
                       class="w-full !min-h-0 rounded-md px-6 py-2.5 bg-white text-neutral-800 placeholder-neutral-400 text-sm outline-none border-0">
                <input type="text" wire:model="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="absolute -left-[9999px]">
                <button type="submit" class="text-white cursor-pointer hover:underline" wire:loading.attr="disabled">{{ __('theme::theme.subscribe') }}</button>
            </div>
            @if ($publicLists->count() > 1)
                <fieldset class="flex flex-wrap gap-3 text-sm text-white/80">
                    <legend class="mb-1">{{ __('newsletter::newsletter.form.lists') }}</legend>
                    @foreach ($publicLists as $list)
                        <label title="{{ $list->description }}" class="font-normal"><input type="checkbox" wire:model="lists" value="{{ $list->id }}"> {{ $list->name }}</label>
                    @endforeach
                </fieldset>
            @endif
            @error('email') <p class="text-sm text-red-300" role="alert">{{ $message }}</p> @enderror
        </form>
    @endif
</div>
