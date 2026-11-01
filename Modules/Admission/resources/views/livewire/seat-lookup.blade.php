{{-- Seat lookup (FR-ADM-17). Contract: wire:model serial, website (honeypot); wire:submit lookup; $answer (status, name, serial, program, building, room, floor, seconds); $buildings (name, image, mine) --}}
<div>
    <form wire:submit="lookup" style="display:flex;gap:var(--space-3, 12px);flex-wrap:wrap;margin-block:var(--space-4, 16px)">
        <label for="seat-serial" class="sr-only">{{ __('admission::admission.result_view.serial') }}</label>
        <input id="seat-serial" type="text" wire:model="serial" required maxlength="50" autocomplete="off" placeholder="{{ __('admission::admission.seats.placeholder') }}" style="flex:1;min-width:220px;padding:var(--space-3, 12px)">
        <input type="text" wire:model="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px">
        <button type="submit" class="btn btn--primary">{{ __('admission::admission.seats.search') }}</button>
    </form>
    @error('serial') <p role="alert">{{ $message }}</p> @enderror

    <div aria-live="polite">
        @if ($answer && $answer['status'] === 'found')
            <dl>
                <dt>{{ __('admission::admission.result_view.name') }}</dt><dd>{{ $answer['name'] }}</dd>
                <dt>{{ __('admission::admission.result_view.serial_short') }}</dt><dd>{{ $answer['serial'] }}</dd>
                @if ($answer['program'])<dt>{{ __('admission::admission.result_view.program') }}</dt><dd>{{ $answer['program'] }}</dd>@endif
                <dt>{{ __('admission::admission.seats.building') }}</dt><dd>{{ $answer['building'] }}</dd>
                <dt>{{ __('admission::admission.seats.room') }}</dt><dd>{{ $answer['room'] }}</dd>
                @if ($answer['floor'])<dt>{{ __('admission::admission.seats.floor') }}</dt><dd>{{ $answer['floor'] }}</dd>@endif
            </dl>
        @elseif ($answer && $answer['status'] === 'too_many')
            <p role="alert">{{ __('admission::admission.result_page.too_many', ['seconds' => $answer['seconds']]) }}</p>
        @elseif ($answer)
            <p role="alert">{{ __('admission::admission.seats.not_found') }}</p>
        @endif
    </div>

    @foreach ($buildings as $building)
        <figure @if ($building['mine']) aria-current="true" @endif><img src="{{ $building['image'] }}" alt="{{ $building['name'] }}" loading="lazy" style="max-width:100%"><figcaption>{{ $building['name'] }}</figcaption></figure>
    @endforeach
</div>
