{{-- "Academic Calendar" page block: the running calendar, the others to switch to. Contract: $data (count), $locale; $calendars (CalendarPage) --}}
@if (($cards = $calendars->cards()) !== [])
    <div class="block block--calendar-sessions" x-data="{ shown: 0 }">
        @if (count($cards) > 1)
            <label>{{ __('calendar::calendar.fields.session_label') }}
                <select x-model.number="shown">
                    @foreach ($cards as $card)
                        <option value="{{ $loop->index }}">{{ $card['title'] }}</option>
                    @endforeach
                </select>
            </label>
        @endif
        @foreach ($cards as $card)
            <section class="calendar-current" x-show="shown === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif style="margin-top:var(--space-4, 16px)">
                <h2><a href="{{ $card['url'] }}">{{ $card['title'] }}</a></h2>
                @if ($card['rows'] === [])
                    <p>{{ __('calendar::calendar.no_dates') }}</p>
                @else
                    <table class="table calendar-table" style="width:100%">
                        <thead><tr><th scope="col">{{ __('calendar::calendar.fields.event') }}</th><th scope="col">{{ __('calendar::calendar.fields.date') }}</th></tr></thead>
                        <tbody>
                            @foreach ($card['rows'] as $row)
                                <tr><td>{{ $row['title'] }}@if ($row['type']) <small>({{ $row['type'] }})</small>@endif</td><td style="white-space:nowrap">{{ $row['date'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>
        @endforeach
    </div>
@endif
