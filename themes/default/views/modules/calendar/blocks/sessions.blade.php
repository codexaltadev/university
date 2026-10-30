{{--
    "Academic Calendar" page block as DIU's /registrar-office/academic-calendar (CalendarSection): the blue card, the session
    dropdown switching calendars in place, Academic Year / Duration / Total Events and the dates.
    Contract: $data (count), $locale; $calendars (CalendarPage: cards, running first)
--}}
@if (($cards = $calendars->cards()) !== [])
    <div class="not-prose" x-data="{ shown: 0 }">
        @foreach ($cards as $card)
            <div x-show="shown === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif>
                @include('theme::partials.calendar.card', ['card' => $card, 'sessions' => $cards, 'switch' => true])
            </div>
        @endforeach
    </div>
@endif
