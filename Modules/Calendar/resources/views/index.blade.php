{{--
    /academic-calendar and /{entity}/academic-calendar (FR-CAL-04). Contract: $current (list of [calendar, events] — running
    now, or the filter's result), $others (Calendar list), $years, $sessions, $faculties (Faculty list; empty on a microsite),
    $audiences (Term list), $audience (?Term), $faculty (?Faculty), $filtered (bool), $base (microsite path or '')
--}}
<x-theme::layout :title="__('calendar::calendar.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('calendar::calendar.plural') }}</h1>

        <form method="get" class="calendar-filters" style="display:flex;flex-wrap:wrap;gap:var(--space-3, 12px);align-items:end;margin-top:var(--space-4, 16px)">
            @if ($years->count() > 1)
                <label>{{ __('calendar::calendar.fields.year') }}
                    <select name="year" onchange="this.form.submit()">
                        <option value="">{{ __('calendar::calendar.all') }}</option>
                        @foreach ($years as $year)
                            <option value="{{ $year }}" @selected((string) request('year') === (string) $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            @if ($sessions->count() > 1)
                <label>{{ __('calendar::calendar.fields.session_label') }}
                    <select name="session" onchange="this.form.submit()">
                        <option value="">{{ __('calendar::calendar.all') }}</option>
                        @foreach ($sessions as $session)
                            <option value="{{ $session }}" @selected(request('session') === $session)>{{ $session }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            @if ($faculties->isNotEmpty())
                <label>{{ __('calendar::calendar.fields.faculties') }}
                    <select name="faculty" onchange="this.form.submit()">
                        <option value="">{{ __('calendar::calendar.all') }}</option>
                        @foreach ($faculties as $item)
                            <option value="{{ $item->slug() }}" @selected($faculty?->is($item))>{{ $item->short_name ?: $item->name }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            @if ($audiences->isNotEmpty())
                <label>{{ __('calendar::calendar.fields.audience') }}
                    <select name="audience" onchange="this.form.submit()">
                        <option value="">{{ __('calendar::calendar.everyone') }}</option>
                        @foreach ($audiences as $term)
                            <option value="{{ $term->slug() }}" @selected($audience?->is($term))>{{ $term->name }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            <noscript><button type="submit" class="button">{{ __('calendar::calendar.filter') }}</button></noscript>
        </form>

        @forelse ($current as $item)
            <section class="calendar-current" style="margin-top:var(--space-8, 32px)">
                <h2><a href="{{ url($item['calendar']->url()) }}">{{ $item['calendar']->title }}</a></h2>
                @include('calendar::partials.meta', ['calendar' => $item['calendar']])
                @include('calendar::partials.table', ['events' => $item['events']])
            </section>
        @empty
            <p style="margin-top:var(--space-8, 32px)">{{ __('calendar::calendar.none') }}</p>
        @endforelse

        @if ($others->isNotEmpty())
            <section class="calendar-others" style="margin-top:var(--space-10, 40px)">
                <h2>{{ $filtered ? __('calendar::calendar.more') : __('calendar::calendar.others') }}</h2>
                <ul style="list-style:none;padding:0;display:grid;gap:var(--space-2, 8px)">
                    @foreach ($others as $calendar)
                        <li style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:var(--space-3, 12px);padding:var(--space-3, 12px) var(--space-4, 16px);border:1px solid var(--color-border);border-radius:var(--radius-sm, 4px)">
                            <a href="{{ url($calendar->url()) }}">{{ $calendar->title }}</a>
                            <span>{{ $calendar->duration_label }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-theme::layout>
