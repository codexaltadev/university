{{-- Table view (FR-CAL-04). Contract: $events (CalendarEvent list, in order) --}}
@if ($events->isEmpty())
    <p>{{ __('calendar::calendar.no_dates') }}</p>
@else
    <div tabindex="0" style="overflow-x:auto">
        <table class="table calendar-table" style="width:100%">
            <thead>
                <tr>
                    <th scope="col">{{ __('calendar::calendar.fields.event') }}</th>
                    <th scope="col">{{ __('calendar::calendar.fields.date') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($events as $event)
                    @php($past = $event->lastDay()?->isPast() && ! $event->lastDay()->isToday())
                    <tr @class(['calendar-past' => $past]) @style(['opacity:.6' => $past])>
                        <td>{{ $event->title }} @include('calendar::partials.type', ['event' => $event])</td>
                        <td style="white-space:nowrap">{{ $event->when() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
