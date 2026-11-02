{{--
    The lookup card of a lookup page (admission test result, seat plan …), one look for all (owner 2026-11-02): a label, the
    field and the search button on one line (stacked on phones), a reset button once there is an answer, the error under it.
    Inside a Livewire view: wire:submit {action}, wire:model {model}, honeypot "website", wire:click "clear".
    <x-theme::lookup-form id="seat-serial" :label="…" :placeholder="…" :button="…" :busy="…" :error="$errors->first('serial')" :clear="(bool) $answer" :clear-label="…" />
--}}
@props(['id', 'label', 'button', 'placeholder' => null, 'busy' => '…', 'icon' => 'search', 'error' => null,
    'clear' => false, 'clearLabel' => null, 'action' => 'lookup', 'model' => 'serial'])
<div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-6 sm:p-8 mb-8">
    <form wire:submit="{{ $action }}">
        <label for="{{ $id }}" class="block text-sm font-semibold text-gray-700 mb-2">{{ $label }}</label>
        <div class="flex flex-col sm:flex-row gap-3">
            <input id="{{ $id }}" type="text" wire:model="{{ $model }}" required maxlength="50" autocomplete="off" placeholder="{{ $placeholder }}"
                   class="flex-1 px-4 py-3 rounded-xl border border-gray-300 text-base focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
            <input type="text" wire:model="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="absolute -left-[9999px]">
            <button type="submit" wire:loading.attr="disabled" wire:target="{{ $action }}"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-semibold text-white bg-secondary hover:bg-primary transition-colors disabled:opacity-50 cursor-pointer">
                <x-theme::icon :name="$icon" class="w-4 h-4" />
                <span wire:loading.remove wire:target="{{ $action }}">{{ $button }}</span>
                <span wire:loading wire:target="{{ $action }}">{{ $busy }}</span>
            </button>
            @if ($clear)
                <button type="button" wire:click="clear" class="inline-flex items-center justify-center px-4 py-3 rounded-xl border border-gray-300 text-gray-600 hover:bg-gray-50 cursor-pointer" aria-label="{{ $clearLabel }}">
                    <x-theme::icon name="close" class="w-4 h-4" />
                </button>
            @endif
        </div>
        @if ($error) <p class="mt-2 text-sm text-red-600" role="alert">{{ $error }}</p> @endif
    </form>
</div>
