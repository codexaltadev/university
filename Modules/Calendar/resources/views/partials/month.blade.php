{{-- Month view (FR-CAL-04). Contract: $months (MonthGrid::build), $events (dates known only in words are listed below) --}}
@php($days = collect(range(0, 6))->map(fn (int $i) => \Illuminate\Support\Carbon::parse('next saturday')->addDays($i)->translatedFormat('D')))
@forelse ($months as $month)
    <section class="calendar-month" style="margin-bottom:var(--space-8, 32px)">
        <h3>{{ $month['month']->translatedFormat('F Y') }}</h3>
        <div tabindex="0" style="overflow-x:auto">
            <table class="table calendar-grid" style="width:100%;table-layout:fixed;min-width:640px">
                <thead><tr>@foreach ($days as $day)<th scope="col">{{ $day }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach ($month['weeks'] as $week)
                        <tr>
                            @foreach ($week as $cell)
                                <td style="vertical-align:top;height:84px;{{ $cell['inMonth'] ? '' : 'opacity:.35' }}">
                                    <div @class(['calendar-day', 'calendar-today' => $cell['day']->isToday()]) style="font-size:.85em;{{ $cell['day']->isToday() ? 'font-weight:700' : '' }}">{{ $cell['day']->day }}</div>
                                    @if ($cell['inMonth'])
                                        @foreach ($cell['events'] as $event)
                                            <div title="{{ $event->title }}" style="font-size:.75em;margin-top:2px;padding:1px 4px;border-radius:3px;color:#fff;background:{{ $event->color() }};overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $event->title }}</div>
                                        @endforeach
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@empty
    <p>{{ __('calendar::calendar.no_dates') }}</p>
@endforelse
@if (($undated = $events->whereNull('starts_on'))->isNotEmpty())
    <h3>{{ __('calendar::calendar.undated') }}</h3>
    @include('calendar::partials.table', ['events' => $undated->values()])
@endif
