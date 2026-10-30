{{--
    /events and /{entity}/events (FR-EVT-03). Contract: $events (paginator; for "calendar" a list of the month's events),
    $categories, $category (?Term), $layout (list / cards / timeline / calendar), $tab (upcoming / past), $month (?Carbon,
    calendar only), $weeks (calendar: list of weeks of [day, inMonth, events]), $base (place path or '')
--}}
<x-theme::layout :title="__('events::events.plural')">
    @php($listing = url($base.'/'.\Modules\Events\Models\Event::PATH))
    @php($query = fn (array $change) => $listing.'?'.http_build_query(array_filter(array_merge(['tab' => $tab === 'past' ? 'past' : null, 'view' => request('view'), 'category' => $category?->slug()], $change))))
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('events::events.plural') }}@if ($category) — {{ $category->name }}@endif</h1>

        <div style="display:flex;flex-wrap:wrap;gap:var(--space-3, 12px);justify-content:space-between;align-items:center;margin-top:var(--space-4, 16px)">
            @if ($layout !== 'calendar')
                <nav aria-label="{{ __('events::events.tabs.label') }}" style="display:flex;gap:var(--space-2, 8px)">
                    <a href="{{ $query(['tab' => null]) }}" @class(['chip', 'chip-active' => $tab === 'upcoming']) @if ($tab === 'upcoming') aria-current="page" @endif>{{ __('events::events.tabs.upcoming') }}</a>
                    <a href="{{ $query(['tab' => 'past']) }}" @class(['chip', 'chip-active' => $tab === 'past']) @if ($tab === 'past') aria-current="page" @endif>{{ __('events::events.tabs.past') }}</a>
                </nav>
            @endif
            <nav aria-label="{{ __('events::events.view') }}" style="display:flex;gap:var(--space-2, 8px)">
                @foreach (\Modules\Events\Support\EventsWebsite::VIEWS as $option)
                    <a href="{{ $query(['view' => $option, 'tab' => $option === 'calendar' ? null : ($tab === 'past' ? 'past' : null)]) }}" @class(['chip', 'chip-active' => $layout === $option])>{{ __("events::events.layouts.{$option}") }}</a>
                @endforeach
            </nav>
        </div>

        @if ($categories->isNotEmpty())
            <nav aria-label="{{ __('events::events.fields.category') }}" style="display:flex;flex-wrap:wrap;gap:var(--space-2, 8px);margin-top:var(--space-3, 12px)">
                <a href="{{ $query(['category' => null]) }}" @class(['chip', 'chip-active' => ! $category])>{{ __('events::events.all') }}</a>
                @foreach ($categories as $item)
                    <a href="{{ $query(['category' => $item->slug()]) }}" @class(['chip', 'chip-active' => $category?->is($item)])>{{ $item->name }}</a>
                @endforeach
            </nav>
        @endif

        @if ($layout === 'calendar')
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:var(--space-6, 24px)">
                <a href="{{ $query(['month' => $month->copy()->subMonth()->format('Y-m'), 'view' => 'calendar']) }}">‹ {{ $month->copy()->subMonth()->translatedFormat('F') }}</a>
                <h2 style="margin:0">{{ $month->translatedFormat('F Y') }}</h2>
                <a href="{{ $query(['month' => $month->copy()->addMonth()->format('Y-m'), 'view' => 'calendar']) }}">{{ $month->copy()->addMonth()->translatedFormat('F') }} ›</a>
            </div>
            <div tabindex="0" style="overflow-x:auto;margin-top:var(--space-3, 12px)">
                <table class="table events-calendar" style="width:100%;table-layout:fixed;min-width:640px">
                    <thead><tr>@foreach ($weeks[0] ?? [] as $cell)<th scope="col">{{ $cell['day']->translatedFormat('D') }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach ($weeks as $week)
                            <tr>
                                @foreach ($week as $cell)
                                    <td style="vertical-align:top;height:90px;{{ $cell['inMonth'] ? '' : 'opacity:.35' }}">
                                        <div style="font-size:.85em;{{ $cell['day']->isToday() ? 'font-weight:700' : '' }}">{{ $cell['day']->day }}</div>
                                        @if ($cell['inMonth'])
                                            @foreach ($cell['events'] as $item)
                                                <a href="{{ url($base.'/'.\Modules\Events\Models\Event::PATH.'/'.$item->slug()) }}" title="{{ $item->title }}" style="display:block;font-size:.75em;margin-top:2px;padding:1px 4px;border-radius:3px;color:#fff;background:var(--color-primary, #034EA2);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $item->title }}</a>
                                            @endforeach
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif ($events->isEmpty())
            <p style="margin-top:var(--space-8, 32px)">{{ $tab === 'past' ? __('events::events.no_past') : __('events::events.no_upcoming') }}</p>
        @else
            <div style="display:grid;gap:var(--space-4, 16px);margin-top:var(--space-6, 24px);{{ $layout === 'cards' ? 'grid-template-columns:repeat(auto-fill,minmax(280px,1fr))' : '' }}{{ $layout === 'timeline' ? 'border-left:3px solid var(--color-primary, #034EA2);padding-left:var(--space-4, 16px)' : '' }}">
                @foreach ($events as $event)
                    @include('events::partials.card', ['event' => $event, 'layout' => $layout, 'base' => $base])
                @endforeach
            </div>
            <div style="margin-top:var(--space-6, 24px)">{{ $events->links() }}</div>
        @endif
    </div>
</x-theme::layout>
