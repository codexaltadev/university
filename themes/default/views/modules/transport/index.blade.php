{{--
    /transport as DIU's /transport: hero with the introduction; "Get Your DIU Transport Pass" (picture, How to Get Your Pass, Why
    It Matters); the running schedule card (title, semester, View Rules & Policy); the stop search; Regular / Shuttle / Friday tabs
    of DIU's route cards (Route ⇆, both departure lists — a trip that has gone or does not run today greyed and struck through, as
    DIU — View Route on Google Map, and the stops as a line with the notes when opened); Transport Fees.
    Texts from Settings › Transport, routes and times from the Transport module.
    Contract: the module's (timetable, group, stops, page), $transport (TransportView)
--}}
<x-theme::layout :title="$transport->title()">
    @include('theme::partials.diu.hero', [
        'title' => $transport->title(),
        'subtitle' => $transport->intro() ? e($transport->intro()) : null,
        'image' => theme_image('page_hero_image', 'images/page-hero.webp'),
        'breadcrumbs' => $transport->breadcrumbs(),
    ])

    <div class="bg-white" x-data="{
            now: new Date(), day: ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][new Date().getDay()],
            gone(t) { const [h, m] = t.time.split(':').map(Number); const d = new Date(); d.setHours(h, m, 0, 0); return d < this.now || t.off.includes(this.day) },
         }">
        <div class="container mx-auto px-4 lg:px-6 py-6">
            @if ($pass = $transport->pass())
                <section class="flex flex-col lg:flex-row items-start gap-8 md:gap-16 my-10">
                    <div class="w-full lg:w-80 shrink-0">
                        <div class="w-full max-w-80 h-96 bg-blue-500 rounded-md flex items-center justify-center overflow-hidden">
                            @if ($pass['image'])
                                <img src="{{ $pass['image'] }}" alt="{{ $pass['alt'] }}" loading="lazy" class="w-full h-full object-cover">
                            @else
                                <x-theme::icon name="user" class="size-32 text-white/80" />
                            @endif
                        </div>
                    </div>
                    <div class="flex-1 space-y-8">
                        <div>
                            <h2 class="text-4xl lg:text-5xl xl:text-[64px] font-bold text-gray-900 mb-2 leading-tight">{{ $pass['title'] }}</h2>
                            @if ($pass['subtitle'])<p class="text-base md:text-2xl text-gray-600">{{ $pass['subtitle'] }}</p>@endif
                        </div>
                        @foreach ([['clipboard', __('transport::transport.pass.how'), $pass['how']], ['sparkles', __('transport::transport.pass.why'), $pass['why']]] as [$icon, $heading, $text])
                            @if ($text)
                                <div class="flex gap-7">
                                    <x-theme::icon :name="$icon" class="size-14 shrink-0 text-secondary" />
                                    <div>
                                        <h3 class="text-lg md:text-[36px] font-semibold text-secondary mb-1">{{ $heading }}</h3>
                                        <p class="text-base md:text-lg text-neutral-600 leading-relaxed">{{ $text }}</p>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($schedule = $transport->schedule())
                <div class="bg-white border border-gray-200 border-l-4 border-l-primary rounded-r-xl p-5 md:p-6 mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 shadow-sm">
                    <div>
                        <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-1">{{ $schedule['title'] }}</h2>
                        @if ($schedule['subtitle'])<p class="font-semibold text-gray-800">{{ $schedule['subtitle'] }}</p>@endif
                        @if ($schedule['semester'])<p class="text-gray-500 font-medium flex items-center gap-1.5 text-sm md:text-base"><x-theme::icon name="calendar" class="w-4 h-4" /> {{ $schedule['semester'] }}</p>@endif
                    </div>
                    @if ($schedule['policy'])
                        <a href="{{ $schedule['policy'] }}" target="_blank" rel="noopener" class="bg-primary hover:bg-secondary text-white px-6 py-2.5 rounded-lg font-medium transition-colors shadow-sm inline-flex items-center gap-2 w-full md:w-auto justify-center hover:no-underline">
                            <x-theme::icon name="file-text" class="w-5 h-5" /> {{ __('transport::transport.policy') }}
                        </a>
                    @endif
                </div>
                @if ($schedule['stale'])<p role="status" class="mb-6 rounded-lg bg-green-50 border border-green-200 px-4 py-2 text-sm text-green-800">{{ $schedule['stale'] }}</p>@endif

                {{-- Stop search --}}
                <div class="mb-8 rounded-xl border border-gray-200 p-5" x-data="{ q: '', routes: @js($transport->finder()),
                        get hits() { const q = this.q.trim().toLowerCase(); return q.length < 2 ? [] : this.routes.filter(r => r.stops.some(s => s.toLowerCase().includes(q))) } }">
                    <label for="transport-stop" class="block text-lg font-semibold text-gray-900">{{ __('transport::transport.finder.label') }}</label>
                    <div class="relative mt-2 max-w-xl">
                        <x-theme::icon name="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-gray-400" />
                        <input id="transport-stop" type="search" x-model="q" list="transport-stops" autocomplete="off" placeholder="{{ __('transport::transport.finder.placeholder') }}"
                               class="w-full rounded-full border border-secondary pl-10 pr-4 py-2.5 text-base outline-none focus:ring-2 focus:ring-primary/30">
                    </div>
                    <datalist id="transport-stops">@foreach ($transport->stops() as $stop)<option value="{{ $stop }}"></option>@endforeach</datalist>
                    <div aria-live="polite" class="mt-3">
                        <p x-show="q.trim().length >= 2 && hits.length === 0" x-cloak class="text-gray-500">{{ __('transport::transport.finder.none') }}</p>
                        <ul x-show="hits.length" x-cloak class="flex flex-wrap gap-2">
                            <template x-for="r in hits" :key="r.group + r.id">
                                <li><a :href="'#route-' + r.id" x-on:click="$dispatch('transport-show', r.group)" class="inline-flex items-center gap-1.5 rounded-full border border-secondary px-4 py-1.5 text-secondary hover:bg-secondary hover:text-white hover:no-underline"><span x-text="r.name"></span> <small class="opacity-70" x-text="'· ' + r.service"></small></a></li>
                            </template>
                        </ul>
                    </div>
                </div>

                {{-- Regular / Shuttle / Friday --}}
                <div x-data="{ tab: @js($transport->openGroup()) }" x-on:transport-show.window="tab = $event.detail">
                    <div class="flex gap-4 mb-8 border-b border-gray-200 overflow-x-auto" role="tablist">
                        @foreach ($transport->groups() as $group)
                            <button type="button" role="tab" x-on:click="tab = @js($group['key'])" x-bind:aria-selected="tab === @js($group['key'])"
                                    x-bind:class="tab === @js($group['key']) ? 'border-b-2 border-primary text-primary' : 'text-gray-500 hover:text-gray-700'"
                                    class="pb-2 px-4 whitespace-nowrap text-lg font-medium transition-colors cursor-pointer">{{ $group['label'] }} <small>({{ count($group['routes']) }})</small></button>
                        @endforeach
                    </div>

                    @foreach ($transport->groups() as $group)
                        <div role="tabpanel" x-show="tab === @js($group['key'])" @if ($group['key'] !== $transport->openGroup()) x-cloak @endif class="mb-8">
                            @foreach ($group['routes'] as $route)
                                <section id="route-{{ $route['id'] }}" class="mb-8 scroll-mt-28" x-data="{ open: false }">
                                    <h3 class="text-lg md:text-2xl font-semibold text-neutral-800 mb-4">{{ $route['name'] }}</h3>
                                    <div class="p-2 sm:p-4 bg-white rounded-lg border transition-all duration-300" x-bind:class="open ? 'border-secondary shadow-lg' : 'border-neutral-300 shadow-md'">
                                        <div class="p-3 sm:p-4 lg:p-6 border-gray-200" x-bind:class="open && 'border-b'">
                                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 lg:gap-8">
                                                <div class="flex items-center justify-between lg:justify-start lg:pr-8 lg:border-r lg:border-neutral-300">
                                                    <div>
                                                        <div class="text-xs sm:text-lg font-extrabold text-secondary mb-1">{{ __('transport::transport.route_label') }}</div>
                                                        <div class="flex items-center gap-2 sm:gap-3">
                                                            <span class="text-base sm:text-lg lg:text-2xl max-w-xs text-neutral-600">{{ $route['name'] }}</span>
                                                            <x-theme::icon name="arrow-right" class="w-4 h-4 sm:w-5 sm:h-5 text-secondary shrink-0" />
                                                        </div>
                                                        @if ($route['days_off'])<p class="mt-1 text-xs text-gray-500">{{ __('transport::transport.day_off') }}: {{ $route['days_off'] }}</p>@endif
                                                    </div>
                                                    <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open" aria-label="{{ __('theme::theme.show_details') }}: {{ $route['name'] }}" class="lg:hidden text-secondary cursor-pointer"><x-theme::icon name="chevron-down" class="w-6 h-6 transition-transform" x-bind:class="open && 'rotate-180'" /></button>
                                                </div>
                                                <div class="flex flex-col sm:flex-row gap-3 sm:gap-6 lg:px-8 lg:border-r lg:border-neutral-300">
                                                    @foreach ([[$route['from'], $route['to_campus'], 'to_campus'], [$route['to'], $route['from_campus'], 'from_campus']] as [$place, $trips, $direction])
                                                        <div class="lg:max-w-[250px]">
                                                            <div class="text-xs sm:text-lg font-extrabold text-secondary">{{ $place ? __('transport::transport.departure_from', ['place' => $place]) : __("transport::transport.{$direction}") }}</div>
                                                            @if ($trips === [])
                                                                <p class="text-sm sm:text-base text-gray-500 mt-2 font-medium">{{ __('transport::transport.no_schedule') }}</p>
                                                            @else
                                                                <div class="flex gap-2 mt-2 flex-wrap">
                                                                    @foreach ($trips as $trip)
                                                                        <span class="border rounded-lg px-4 py-1 text-xs sm:text-sm tabular-nums"
                                                                              x-bind:class="gone(@js(['time' => $trip['time'], 'off' => $trip['off']])) ? 'border-gray-200 bg-gray-100 text-gray-400 line-through' : 'border-gray-300 bg-white'">{{ $trip['label'] }}@if ($trip['shuttle'])<small> · {{ __('transport::transport.shuttle') }}</small>@endif</span>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <div class="flex items-center gap-3 sm:gap-4">
                                                    @if ($route['map'])
                                                        <x-theme::button :href="$route['map']" target="_blank" variant="outlinePrimary" rounded="full" padding="px-5 py-2.5" size="text-base" class="text-primary !border-primary">
                                                            <span class="hidden sm:inline">{{ __('transport::transport.view_map') }}</span><span class="sm:hidden">{{ __('transport::transport.view_map_short') }}</span>
                                                        </x-theme::button>
                                                    @endif
                                                    @if ($route['stops'] !== [] || $route['notes'] !== [])
                                                        <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open" aria-label="{{ __('transport::transport.stops_toggle') }}" class="hidden lg:grid place-items-center bg-neutral-200 text-neutral-600 p-3 rounded-full cursor-pointer">
                                                            <x-theme::icon name="chevron-down" class="w-4 h-4 transition-transform" x-bind:class="open && 'rotate-180'" />
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div x-show="open" x-collapse x-cloak class="px-4 lg:px-8 py-6">
                                            @if ($route['stops'] !== [])
                                                <ol class="flex flex-col lg:flex-row lg:flex-wrap gap-y-6 lg:gap-y-10 overflow-hidden" aria-label="{{ trans_choice('transport::transport.stops', count($route['stops']), ['count' => count($route['stops'])]) }}">
                                                    @foreach ($route['stops'] as $stop)
                                                        <li class="relative flex lg:flex-col items-center gap-3 lg:gap-3 lg:w-[12.5%] lg:min-w-[110px]">
                                                            @unless ($loop->last)<span class="absolute bg-neutral-400 left-[15px] top-8 h-[calc(100%+0.5rem)] w-1 lg:left-1/2 lg:top-[15px] lg:h-1 lg:w-full" aria-hidden="true"></span>@endunless
                                                            <span class="relative z-10 grid place-items-center size-8 shrink-0 rounded-full bg-white border-4 border-neutral-300 shadow-lg"><span class="size-3 rounded-full bg-primary"></span></span>
                                                            <span @class(['text-sm md:text-base lg:text-center leading-tight', 'font-bold text-black' => $loop->first || $loop->last, 'text-gray-700' => ! $loop->first && ! $loop->last])>{{ $stop }}</span>
                                                        </li>
                                                    @endforeach
                                                </ol>
                                            @endif
                                            @if ($route['notes'] !== [])
                                                <div class="mt-6 pt-4 border-t border-gray-100">
                                                    <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl flex items-start gap-3">
                                                        <x-theme::icon name="alert-circle" class="w-5 h-5 text-primary shrink-0 mt-0.5" />
                                                        <ol class="flex-1 text-sm md:text-[15px] text-gray-700 leading-relaxed list-decimal pl-4">@foreach ($route['notes'] as $note)<li>{{ $note }}</li>@endforeach</ol>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @else
                <p role="status" class="my-10 text-center text-gray-500">{{ __('transport::transport.unavailable') }}</p>
            @endif

            @if (($fees = $transport->fees()) !== [])
                <section class="mb-8 md:mb-20">
                    <h2 class="text-lg md:text-2xl font-semibold text-neutral-800 mb-6">{{ __('transport::transport.fees.title') }}</h2>
                    <div class="border border-secondary rounded-xl overflow-x-auto bg-white" tabindex="0">
                        <table class="w-full min-w-[420px]">
                            <thead>
                                <tr class="text-base md:text-2xl text-gray-600 font-normal">
                                    <th scope="col" class="p-6 text-left font-normal">{{ __('transport::transport.fees.route') }}</th>
                                    <th scope="col" class="p-6 text-center font-normal border-l border-gray-200">{{ __('transport::transport.fees.tri') }}</th>
                                    <th scope="col" class="p-6 text-center font-normal border-l border-gray-200">{{ __('transport::transport.fees.bi') }}</th>
                                </tr>
                            </thead>
                            <tbody class="text-sky-700">
                                @foreach ($fees as $fee)
                                    <tr class="border-t border-gray-300">
                                        <th scope="row" class="px-6 py-5 text-left text-lg md:text-3xl font-medium">{{ $fee['route'] }}</th>
                                        <td class="px-6 py-5 text-center text-lg md:text-3xl font-medium border-l border-gray-200">{{ $fee['tri'] }}</td>
                                        <td class="px-6 py-5 text-center text-lg md:text-3xl font-medium border-l border-gray-200">{{ $fee['bi'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($note = $transport->feesNote())<p class="mt-3 text-gray-600">{{ $note }}</p>@endif
                </section>
            @endif
        </div>
    </div>
</x-theme::layout>
