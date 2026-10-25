{{--
    Admission updates sign-up as DIU's NotificationForm (/admission): link icon and "Enter Email or WhatsApp number for
    real-time notifications.", Email / WhatsApp, the field, "Get Update", and "Join WhatsApp Group" when Settings has its link.
    Contract (Admission module): wire:model channel, contact, website (honeypot); wire:submit subscribe; $done, $channels, $whatsappGroup
--}}
<div class="rounded-xl border border-slate-200 bg-white p-4">
    <div class="flex items-center gap-2 border-b border-neutral-600 pb-2 mb-4">
        <x-theme::icon name="link" class="w-4 h-4 text-slate-500" />
        <p class="text-base md:text-xl font-medium text-slate-700">{!! __('admission::admission.notify') !!}</p>
    </div>
    @if ($done)
        <p class="text-green-700" role="status">{{ $done }}</p>
    @else
        <form wire:submit="subscribe" class="flex flex-wrap gap-3 items-center">
            <div class="flex items-center gap-4" role="radiogroup">
                @foreach ($channels as $option)
                    <label class="flex items-center gap-2 text-sm cursor-pointer has-checked:text-primary">
                        <input type="radio" wire:model.live="channel" value="{{ $option }}" class="accent-primary"> {{ __("admission::admission.channels.{$option}") }}
                    </label>
                @endforeach
            </div>
            <input type="{{ $channel === 'email' ? 'email' : 'tel' }}" wire:model="contact" required maxlength="191" aria-label="{{ __('admission::admission.fields.contact') }}"
                   placeholder="{{ $channel === 'email' ? __('admission::admission.subscribe.email_placeholder') : __('admission::admission.subscribe.phone_placeholder') }}"
                   class="flex-1 min-w-52 rounded-full border border-neutral-800 px-5 py-3 text-base outline-none focus:ring-2 focus:ring-primary/30">
            <input type="text" wire:model="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="absolute -left-[9999px]">
            <button type="submit" wire:loading.attr="disabled" class="rounded-full border border-neutral-800 px-5 py-3 text-base font-medium text-secondary hover:bg-secondary hover:text-white transition-colors cursor-pointer disabled:opacity-50">{{ __('admission::admission.get_update') }}</button>
            @if ($whatsappGroup)
                <a href="{{ $whatsappGroup }}" target="_blank" rel="noopener" class="rounded-full bg-green-500 px-6 py-3 text-base font-medium text-white hover:bg-green-600 hover:no-underline">{{ __('admission::admission.join_whatsapp') }}</a>
            @endif
            @error('contact') <p class="w-full text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
        </form>
    @endif
</div>
