<div class="uwms-working-in" style="display:flex;align-items:center;gap:.5rem;margin-inline-end:.75rem">
    <label for="uwms-working-in" style="font-size:.75rem;opacity:.7;white-space:nowrap">{{ __('platform::hub.working_in') }}</label>
    <x-filament::input.wrapper>
        <x-filament::input.select id="uwms-working-in" wire:model.live="owner">
            @foreach ($options as $key => $option)
                @if (is_array($option))
                    <optgroup label="{{ $key }}">
                        @foreach ($option as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </optgroup>
                @else
                    <option value="{{ $key }}">{{ $option }}</option>
                @endif
            @endforeach
        </x-filament::input.select>
    </x-filament::input.wrapper>
</div>
