{{--
    /academic-calendar as DIU's Registrar office shows it (DIUFronend registrar-office/academic-calendar): hero; the
    filters (year, session, faculty, audience) beyond DIU; a card per running calendar — session switcher, year,
    duration, total, the dates. Contract: the module's (current, others, years, sessions, faculties, audiences,
    audience, faculty, filtered, base), $page (CalendarPage)
--}}
<x-theme::layout :title="__('calendar::calendar.plural')">
    @include('theme::partials.diu.hero', [
        'title' => __('calendar::calendar.plural'),
        'subtitle' => e(__('calendar::calendar.page.subtitle')),
        'image' => theme_image('page_hero_image', 'images/page-hero.webp'),
        'breadcrumbs' => [['label' => __('theme::theme.home'), 'url' => url($base ?: '/')], ['label' => __('calendar::calendar.plural'), 'url' => $page->listingUrl()]],
    ])

    <div class="min-h-screen bg-gray-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-10">
            <form method="get" class="flex flex-wrap items-end gap-4 bg-white rounded-2xl border border-gray-200 p-5">
                @foreach ([
                    ['name' => 'year', 'label' => __('calendar::calendar.fields.year'), 'all' => __('calendar::calendar.all'), 'options' => $years->mapWithKeys(fn ($y) => [(string) $y => (string) $y])->all(), 'value' => (string) request('year')],
                    ['name' => 'session', 'label' => __('calendar::calendar.fields.session_label'), 'all' => __('calendar::calendar.all'), 'options' => $sessions->mapWithKeys(fn ($s) => [(string) $s => (string) $s])->all(), 'value' => (string) request('session')],
                    ['name' => 'faculty', 'label' => __('calendar::calendar.fields.faculties'), 'all' => __('calendar::calendar.all'), 'options' => $faculties->mapWithKeys(fn ($f) => [$f->slug() => $f->short_name ?: $f->name])->all(), 'value' => (string) $faculty?->slug()],
                    ['name' => 'audience', 'label' => __('calendar::calendar.fields.audience'), 'all' => __('calendar::calendar.everyone'), 'options' => $audiences->mapWithKeys(fn ($t) => [$t->slug() => $t->name])->all(), 'value' => (string) $audience?->slug()],
                ] as $filter)
                    @if (count($filter['options']) > 1 || ($filter['name'] === 'audience' && count($filter['options']) > 0))
                        <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">
                            {{ $filter['label'] }}
                            <select name="{{ $filter['name'] }}" onchange="this.form.submit()" class="h-10 min-w-40 rounded-lg border border-gray-200 bg-white px-3 text-gray-900">
                                <option value="">{{ $filter['all'] }}</option>
                                @foreach ($filter['options'] as $value => $label)
                                    <option value="{{ $value }}" @selected($filter['value'] === (string) $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif
                @endforeach
                <noscript><button type="submit" class="h-10 px-4 rounded-lg bg-gray-900 text-white text-sm">{{ __('calendar::calendar.filter') }}</button></noscript>
            </form>

            @forelse ($page->cards() as $card)
                @include('theme::partials.calendar.card', ['card' => $card, 'sessions' => $page->sessions()])
            @empty
                <p class="text-center text-gray-500 py-12">{{ __('calendar::calendar.none') }}</p>
            @endforelse

            @if ($others->isNotEmpty())
                <section class="bg-white rounded-2xl border border-gray-200 p-6 sm:p-8" aria-labelledby="calendar-others">
                    <h2 id="calendar-others" class="text-xl font-bold text-gray-900 mb-4">{{ $filtered ? __('calendar::calendar.more') : __('calendar::calendar.others') }}</h2>
                    <ul class="divide-y divide-gray-100">
                        @foreach ($others as $calendar)
                            <li class="flex flex-wrap justify-between gap-3 py-3">
                                <a href="{{ url($calendar->url()) }}" class="font-medium text-primary inline-flex items-center gap-2"><x-theme::icon name="calendar" class="w-4 h-4" />{{ $calendar->title }}</a>
                                <span class="text-sm text-gray-500">{{ $calendar->duration_label }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>
</x-theme::layout>
