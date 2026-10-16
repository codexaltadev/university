{{--
    /academic-calendar/{slug} (FR-CAL-04/05/06). Contract: $calendar, $events (CalendarEvent list, audience-filtered),
    $view (table / month / timeline), $months (MonthGrid::build), $audiences (Term list), $audience (?Term),
    $hardCopy (?MediaItem: the PDF or picture)
--}}
<x-theme::layout :title="$calendar->title">
    @php($query = fn (array $change) => '?'.http_build_query(array_filter(array_merge(['view' => request('view'), 'audience' => $audience?->slug()], $change))))
    <div class="container" style="padding-block:var(--space-8, 32px)">
        <nav class="breadcrumb" aria-label="{{ __('platform::relations.breadcrumb') }}">
            <ol>
                <li><a href="{{ url('/') }}">{{ __('platform::relations.home') }}</a><span aria-hidden="true"> › </span></li>
                <li><a href="{{ url('/'.\Modules\Calendar\Models\Calendar::PATH) }}">{{ __('calendar::calendar.plural') }}</a><span aria-hidden="true"> › </span></li>
                <li><span aria-current="page">{{ $calendar->title }}</span></li>
            </ol>
        </nav>

        <h1>{{ $calendar->title }}</h1>
        @include('calendar::partials.meta', ['calendar' => $calendar])

        <div style="display:flex;flex-wrap:wrap;gap:var(--space-3, 12px);align-items:center;justify-content:space-between;margin-block:var(--space-4, 16px)">
            <nav class="calendar-views" aria-label="{{ __('calendar::calendar.view') }}" style="display:flex;flex-wrap:wrap;gap:var(--space-2, 8px)">
                @foreach (\Modules\Calendar\Support\CalendarWebsite::VIEWS as $option)
                    <a href="{{ $query(['view' => $option]) }}" @class(['chip', 'chip-active' => $view === $option]) @if ($view === $option) aria-current="true" @endif>{{ __("calendar::calendar.layouts.{$option}") }}</a>
                @endforeach
            </nav>
            @if ($audiences->isNotEmpty())
                <nav class="calendar-audiences" aria-label="{{ __('calendar::calendar.fields.audience') }}" style="display:flex;flex-wrap:wrap;gap:var(--space-2, 8px)">
                    <a href="{{ $query(['audience' => null]) }}" @class(['chip', 'chip-active' => ! $audience])>{{ __('calendar::calendar.everyone') }}</a>
                    @foreach ($audiences as $term)
                        <a href="{{ $query(['audience' => $term->slug()]) }}" @class(['chip', 'chip-active' => $audience?->is($term)])>{{ $term->name }}</a>
                    @endforeach
                </nav>
            @endif
        </div>

        @include('calendar::partials.'.$view, ['events' => $events, 'months' => $months])

        <p style="display:flex;flex-wrap:wrap;gap:var(--space-3, 12px);margin-top:var(--space-6, 24px)">
            @if ($hardCopy)
                <a href="{{ $hardCopy->url() }}" class="button button-primary" target="_blank" rel="noopener">{{ __('calendar::calendar.download') }}</a>
            @endif
            @if ($events->whereNotNull('starts_on')->isNotEmpty())
                <a href="{{ url($calendar->url().'.ics').($audience ? '?audience='.$audience->slug() : '') }}" class="button">{{ __('calendar::calendar.add_to_calendar') }}</a>
            @endif
        </p>
    </div>
</x-theme::layout>
