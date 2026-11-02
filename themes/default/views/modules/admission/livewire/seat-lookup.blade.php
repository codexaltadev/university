{{--
    Seat lookup as DIU's /seat-plan: the search card (Application Serial Number, Search Seat, reset); found — a green
    "Seat Plan Allocated" card (student, serial, program; Building, Room / Hall, Floor / Level tiles); not found — a red
    note; then "Examination Venues & Buildings" with the applicant's building marked.
    Contract (Admission module): wire:model serial, website (honeypot); wire:submit lookup; wire:click clear;
    $answer (status, name, serial, program, building, room, floor, seconds); $buildings (name, image, mine)
--}}
<div>
    <x-theme::lookup-form id="seat-serial" :label="__('admission::admission.result_view.serial')" :placeholder="__('admission::admission.seats.placeholder')"
        :button="__('admission::admission.seats.search')" :busy="__('admission::admission.seats.searching')" :error="$errors->first('serial')"
        :clear="(bool) $answer" :clear-label="__('admission::admission.seats.reset')" />

    <div aria-live="polite">
        @if ($answer && $answer['status'] === 'found')
            <div class="bg-white rounded-2xl shadow-sm border border-emerald-200 overflow-hidden mb-8">
                <div class="bg-emerald-50 border-b border-emerald-200 px-6 py-3 flex items-center gap-2 text-emerald-800 font-semibold text-sm">
                    <x-theme::icon name="check" class="w-4 h-4" /><span>{{ __('admission::admission.seats.allocated') }}</span>
                </div>
                <div class="p-6 sm:p-8">
                    <p class="text-xl sm:text-2xl font-bold text-secondary">{{ $answer['name'] }}</p>
                    <p class="text-sm text-gray-600 mt-1">{{ __('admission::admission.result_view.serial_short') }}: <span class="font-mono font-semibold text-gray-800">{{ $answer['serial'] }}</span>
                        @if ($answer['program'])<span class="mx-1">·</span>{{ $answer['program'] }}@endif</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6">
                        @foreach ([['building', 'seats.building', $answer['building']], ['grid', 'seats.room', $answer['room']], ['trending-up', 'seats.floor', $answer['floor']]] as [$icon, $label, $value])
                            @if ($value)
                                <div class="rounded-xl bg-blue-50/70 border border-blue-100 p-4">
                                    <div class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-primary"><x-theme::icon :name="$icon" class="w-4 h-4" /><span>{{ __('admission::admission.'.$label) }}</span></div>
                                    <p class="mt-1 text-lg font-bold text-secondary">{{ $value }}</p>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @elseif ($answer && $answer['status'] === 'too_many')
            <div class="rounded-2xl p-5 mb-8 border flex items-start gap-3 bg-blue-50 border-blue-200 text-primary" role="alert">
                <x-theme::icon name="alert-circle" class="size-5 shrink-0 mt-0.5" />
                <p class="text-sm">{{ __('admission::admission.result_page.too_many', ['seconds' => $answer['seconds']]) }}</p>
            </div>
        @elseif ($answer)
            <div class="rounded-2xl p-5 mb-8 border flex items-start gap-3 bg-red-50 border-red-200 text-red-700" role="alert">
                <x-theme::icon name="alert-circle" class="size-5 shrink-0 mt-0.5" />
                <div>
                    <h3 class="text-sm font-semibold text-red-800">{{ __('admission::admission.seats.not_found_title') }}</h3>
                    <p class="text-sm mt-1">{{ __('admission::admission.seats.not_found') }}</p>
                </div>
            </div>
        @endif
    </div>

    @if ($buildings !== [])
        <section class="mt-4">
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2 mb-6">
                <div>
                    <div class="inline-flex items-center gap-1.5 text-xs font-bold text-primary uppercase tracking-wider mb-1"><x-theme::icon name="map-pin" class="w-3.5 h-3.5" /><span>{{ __('admission::admission.seats.campus') }}</span></div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-secondary tracking-tight">{{ __('admission::admission.seats.venues') }}</h2>
                    <p class="text-xs sm:text-sm text-gray-600 mt-1">{{ __('admission::admission.seats.venues_text') }}</p>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($buildings as $building)
                    <a href="{{ $building['image'] }}" target="_blank" rel="noopener" @class(['group block rounded-2xl overflow-hidden bg-white border transition-shadow hover:shadow-md', 'border-amber-400 ring-2 ring-amber-300' => $building['mine'], 'border-gray-200' => ! $building['mine']])>
                        <div class="relative aspect-[4/3] overflow-hidden">
                            <img src="{{ $building['image'] }}" alt="{{ $building['name'] }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @if ($building['mine'])
                                <span class="absolute top-3 left-3 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-400 text-amber-950 text-xs font-bold"><x-theme::icon name="map-pin" class="w-3.5 h-3.5" />{{ __('admission::admission.seats.yours') }}</span>
                            @endif
                        </div>
                        <p class="px-4 py-3 font-semibold text-secondary">{{ $building['name'] }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
