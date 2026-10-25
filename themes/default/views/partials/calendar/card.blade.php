{{--
    DIU's Academic Calendar card (Registrar office page): blue head with the title and the session to switch to,
    then Academic Year / Duration / Total Events and the dates with their type. Contract: $card (CalendarPage card),
    $sessions (list of [title, url, current]), $partial + $partialData (optional: the module's month / timeline in place of the table)
--}}
<section class="bg-white rounded-2xl border border-gray-200 overflow-hidden" aria-label="{{ $card['title'] }}">
    <div class="bg-gradient-to-r from-primary to-secondary text-white p-6 sm:p-8">
        <div class="flex flex-wrap items-center gap-3">
            <x-theme::icon name="calendar" class="w-7 h-7" />
            <h2 class="text-2xl sm:text-3xl font-bold"><a href="{{ $card['url'] }}" class="text-inherit hover:no-underline">{{ __('calendar::calendar.plural') }}</a></h2>
            @if ($card['running'])<span class="ml-auto text-xs font-semibold bg-white/20 px-3 py-1 rounded-full">{{ __('calendar::calendar.running') }}</span>@endif
        </div>
        <div class="relative mt-5 inline-block" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
            <button type="button" @click="open = ! open" :aria-expanded="open" class="flex items-center gap-3 min-w-[280px] justify-between bg-white/10 hover:bg-white/15 border border-white/30 rounded-xl px-4 py-3 font-semibold cursor-pointer">
                <span class="flex items-center gap-2"><x-theme::icon name="calendar" class="w-4 h-4" />{{ $card['title'] }}</span>
                <x-theme::icon name="chevron-down" class="w-4 h-4 transition-transform" ::class="open && 'rotate-180'" />
            </button>
            @if (count($sessions) > 1)
                <div x-show="open" x-cloak x-transition class="absolute z-20 left-0 mt-2 min-w-full max-h-72 overflow-y-auto bg-white text-gray-800 rounded-xl shadow-2xl border border-gray-100 py-2">
                    @foreach ($sessions as $session)
                        <a href="{{ $session['url'] }}" @class(['block px-4 py-2 text-sm hover:bg-blue-50 hover:no-underline', 'font-semibold text-primary' => $session['title'] === $card['title'], 'text-gray-700' => $session['title'] !== $card['title']])>{{ $session['title'] }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="p-6 sm:p-8">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="rounded-xl bg-blue-50 p-4"><p class="text-sm text-gray-600">{{ __('calendar::calendar.page.academic_year') }}</p><p class="mt-1 font-semibold text-gray-900">{{ $card['year'] ?? '—' }}</p></div>
            <div class="rounded-xl bg-green-50 p-4"><p class="text-sm text-gray-600">{{ __('calendar::calendar.page.duration') }}</p><p class="mt-1 font-semibold text-gray-900">{{ $card['duration'] ?? '—' }}</p></div>
            <div class="rounded-xl bg-purple-50 p-4"><p class="text-sm text-gray-600">{{ __('calendar::calendar.page.total') }}</p><p class="mt-1 font-semibold text-gray-900">{{ $card['total'] }}</p></div>
        </div>

        @if (filled($partial ?? null))
            @include($partial, $partialData ?? [])
        @elseif ($card['rows'] === [])
            <p class="text-gray-500 py-6 text-center">{{ __('calendar::calendar.no_dates') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-700"><tr><th scope="col" class="px-6 py-4 font-medium">{{ __('calendar::calendar.page.event') }}</th><th scope="col" class="px-6 py-4 font-medium w-1/3">{{ __('calendar::calendar.page.date') }}</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($card['rows'] as $row)
                            <tr>
                                <td class="px-6 py-5 text-gray-900">
                                    {{ $row['title'] }}
                                    @if ($row['type'])<span class="ml-2 align-middle text-xs font-semibold text-white px-2 py-0.5 rounded-full" style="background:{{ $row['color'] }}">{{ $row['type'] }}</span>@endif
                                </td>
                                <td class="px-6 py-5 text-sm text-gray-600"><span class="inline-flex items-center gap-2"><x-theme::icon name="clock" class="w-4 h-4 text-gray-400" />{{ $row['date'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
