{{--
    DIU event page (DIUFronend app/(public)/events-detail/[id]): hero with the title, then the event —
    picture, title, text — beside "Event Details" (department, date, time, location, category,
    faculty). Beyond DIU, what the event has in Admin: register / join / add-to-calendar, speakers,
    photos and what other modules add (albums). Contract: $event, $speakers, $gallery, $base, $page (EventPage)
--}}
<x-theme::layout :title="$event->title">
    @include('theme::partials.diu.hero', [
        'title' => $page->heroTitle(),
        'subtitle' => null,
        'image' => media_url(settings('events.event_image'), 'large') ?? theme_asset('images/event-hero.jpg'),
        'breadcrumbs' => [['label' => __('theme::theme.home'), 'url' => url($base ?: '/')], ['label' => __('events::events.plural'), 'url' => $page->listingUrl()], ['label' => $event->title, 'url' => request()->url()]],
    ])

    <div class="min-h-screen bg-gray-50">
        <div class="container mx-auto px-4 py-12">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-8 min-w-0">
                    <article class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        @if ($image = $page->image())
                            <div class="aspect-video w-full overflow-hidden">
                                <img src="{{ $image->url('large') ?? $image->url() }}" alt="{{ $image->alt ?? $event->title }}" class="w-full h-full object-cover">
                            </div>
                        @endif
                        <div class="p-8">
                            <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-6 leading-tight">{{ $event->title }}</h1>
                            @if (filled($event->summary))<p class="text-lg text-gray-600 mb-6">{{ $event->summary }}</p>@endif
                            @if ($body = $page->body())
                                @include('theme::partials.page.body', ['body' => $body])
                            @elseif (blank($event->summary))
                                <p class="text-gray-600 text-center py-8">{{ __('events::events.page.no_content') }}</p>
                            @endif
                        </div>
                    </article>

                    @if ($speakers->isNotEmpty())
                        <section class="bg-white rounded-2xl shadow-lg p-8" aria-labelledby="event-speakers">
                            <h2 id="event-speakers" class="text-2xl font-bold text-gray-900 mb-6">{{ __('events::events.sections.speakers') }}</h2>
                            <div class="grid sm:grid-cols-2 gap-4">
                                @foreach ($speakers as $person)
                                    <div class="flex items-center gap-4 border border-gray-100 rounded-xl p-4">
                                        <div class="w-14 h-14 rounded-full overflow-hidden bg-gray-100 flex items-center justify-center shrink-0">
                                            @if ($photo = $person->photoUrl())<img src="{{ $photo }}" alt="" class="w-full h-full object-cover">@else<x-theme::icon name="user" class="w-6 h-6 text-gray-400" />@endif
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-900">@if ($url = $person->profileUrl())<a href="{{ $url }}" class="hover:text-primary">{{ $person->name() }}</a>@else{{ $person->name() }}@endif</p>
                                            <p class="text-sm text-gray-500">{{ $person->roleLabel }}@if ($person->designation()) · {{ $person->designation() }}@endif</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if ($gallery->isNotEmpty())
                        <section class="bg-white rounded-2xl shadow-lg p-8" aria-labelledby="event-gallery">
                            <h2 id="event-gallery" class="text-2xl font-bold text-gray-900 mb-6">{{ __('events::events.fields.gallery') }}</h2>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                @foreach ($gallery as $item)
                                    <a href="{{ $item->url('large') }}" target="_blank" rel="noopener" class="block aspect-[4/3] rounded-lg overflow-hidden bg-gray-100"><img src="{{ $item->url('medium') }}" alt="{{ $item->alt ?? '' }}" loading="lazy" class="w-full h-full object-cover hover:scale-105 transition-transform"></a>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @foreach ($page->tabs() as $tab)
                        <section id="{{ $tab['key'] }}" class="bg-white rounded-2xl shadow-lg p-8">
                            <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ $tab['label'] }}</h2>
                            {!! $tab['html'] !!}
                        </section>
                    @endforeach
                </div>

                <aside class="lg:col-span-1">
                    <div class="bg-white rounded-2xl p-6 sticky top-28">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">{{ __('events::events.page.details') }}</h2>
                        <div class="space-y-4">
                            @foreach ($page->details() as $detail)
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center mt-1 shrink-0"><x-theme::icon :name="$detail['icon']" class="w-4 h-4 text-gray-600" /></div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-500">{{ $detail['label'] }}</p>
                                        @if ($detail['key'] === 'category')
                                            <span class="inline-block px-3 py-1 bg-gray-100 text-gray-800 text-sm font-medium rounded-full">{{ $detail['value'] }}</span>
                                        @else
                                            <p class="text-gray-900 font-medium">{{ $detail['value'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if (array_filter($actions = $page->actions()))
                            <div class="mt-8 space-y-3">
                                @if ($actions['register'])
                                    <a href="{{ $actions['register'] }}" target="_blank" rel="noopener" class="w-full bg-primary hover:bg-secondary text-white font-medium py-3 px-4 rounded-lg transition-colors flex items-center justify-center gap-2 hover:no-underline">{{ __('events::events.register') }}</a>
                                    @if ($actions['register_until'])<p class="text-xs text-gray-500 text-center">{{ __('events::events.register_until', ['date' => $actions['register_until']]) }}</p>@endif
                                @elseif ($actions['closed'])
                                    <p class="w-full bg-gray-100 text-gray-500 font-medium py-3 px-4 rounded-lg text-center">{{ __('events::events.registration_closed') }}</p>
                                @endif
                                @if ($actions['online'])
                                    <a href="{{ $actions['online'] }}" target="_blank" rel="noopener" class="w-full border border-primary text-primary font-medium py-3 px-4 rounded-lg flex items-center justify-center gap-2 hover:no-underline"><x-theme::icon name="video" class="w-4 h-4" />{{ __('events::events.join_online') }}</a>
                                @endif
                                @if ($actions['ics'])
                                    <a href="{{ $actions['ics'] }}" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-900 font-medium py-3 px-4 rounded-lg transition-colors flex items-center justify-center gap-2 hover:no-underline"><x-theme::icon name="download" class="w-4 h-4" />{{ __('events::events.add_to_calendar') }}</a>
                                    <a href="{{ $actions['google'] }}" target="_blank" rel="noopener" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-900 font-medium py-3 px-4 rounded-lg transition-colors flex items-center justify-center gap-2 hover:no-underline">Google Calendar</a>
                                @endif
                            </div>
                        @endif
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-theme::layout>
