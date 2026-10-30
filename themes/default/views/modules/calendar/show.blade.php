{{--
    /academic-calendar/{slug}: one calendar as DIU's card, with (beyond DIU) the table / month / timeline views, the
    audience, the printed copy and .ics. Contract: $calendar, $events, $view, $months, $audiences, $audience, $hardCopy, $page (CalendarPage)
--}}
<x-theme::layout :title="$calendar->title">
    @include('theme::partials.diu.hero', [
        'title' => $calendar->title,
        'subtitle' => e(__('calendar::calendar.page.subtitle')),
        'image' => theme_image('page_hero_image', 'images/page-hero.webp'),
        'breadcrumbs' => [['label' => __('theme::theme.home'), 'url' => url('/')], ['label' => __('calendar::calendar.plural'), 'url' => $page->listingUrl()], ['label' => $calendar->title, 'url' => request()->url()]],
    ])

    <div class="min-h-screen bg-gray-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <nav class="flex gap-1 bg-gray-100 rounded-xl p-1" aria-label="{{ __('calendar::calendar.view') }}">
                    @foreach (['table', 'month', 'timeline'] as $option)
                        <a href="?{{ http_build_query(array_filter(['view' => $option, 'audience' => $audience?->slug()])) }}" @if ($view === $option) aria-current="true" @endif
                           @class(['px-4 py-2 rounded-lg text-sm font-medium hover:no-underline', 'bg-white text-gray-900 shadow-sm' => $view === $option, 'text-gray-600 hover:text-gray-900' => $view !== $option])>{{ __("calendar::calendar.layouts.{$option}") }}</a>
                    @endforeach
                </nav>
                @if ($audiences->isNotEmpty())
                    <nav class="flex flex-wrap gap-2" aria-label="{{ __('calendar::calendar.fields.audience') }}">
                        <a href="?{{ http_build_query(array_filter(['view' => request('view')])) }}" @class(['px-4 py-1.5 rounded-full text-sm font-medium hover:no-underline', 'bg-gray-900 text-white' => ! $audience, 'bg-white border border-gray-200 text-gray-700' => $audience])>{{ __('calendar::calendar.everyone') }}</a>
                        @foreach ($audiences as $term)
                            <a href="?{{ http_build_query(array_filter(['view' => request('view'), 'audience' => $term->slug()])) }}" @class(['px-4 py-1.5 rounded-full text-sm font-medium hover:no-underline', 'bg-gray-900 text-white' => $audience?->is($term), 'bg-white border border-gray-200 text-gray-700' => ! $audience?->is($term)])>{{ $term->name }}</a>
                        @endforeach
                    </nav>
                @endif
            </div>

            @foreach ($page->cards() as $card)
                @include('theme::partials.calendar.card', [
                    'card' => $card,
                    'sessions' => $page->sessions(),
                    'partial' => $view === 'table' ? null : 'calendar::partials.'.$view,
                    'partialData' => ['events' => $events, 'months' => $months],
                ])
            @endforeach

            <div class="flex flex-wrap gap-3">
                @if ($hardCopy)<a href="{{ $hardCopy->url() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-5 py-3 rounded-lg bg-gray-900 text-white text-sm font-medium hover:no-underline"><x-theme::icon name="download" class="w-4 h-4" />{{ __('calendar::calendar.download') }}</a>@endif
                @if ($events->whereNotNull('starts_on')->isNotEmpty())
                    <a href="{{ url($calendar->url().'.ics').($audience ? '?audience='.$audience->slug() : '') }}" class="inline-flex items-center gap-2 px-5 py-3 rounded-lg bg-white border border-gray-200 text-gray-900 text-sm font-medium hover:no-underline"><x-theme::icon name="calendar" class="w-4 h-4" />{{ __('calendar::calendar.add_to_calendar') }}</a>
                @endif
            </div>
        </div>
    </div>
</x-theme::layout>
