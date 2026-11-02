{{--
    An event /events/{slug} (UI/UX Part B §12.5): hero with the state, a details card (date, time, place …) with
    register / online / calendar buttons, the text, speakers, what other modules add. Contract: $event, $speakers, $gallery,
    $base, $page (EventPage: listingUrl, heroTitle, state, image, body, details, actions, tabs)
--}}
<x-theme::layout :title="$event->title">
    <x-theme::page-hero :title="$event->title" :text="$event->summary" :image="$page->image()?->url('large')"
                        :crumbs="[['label' => __('events::events.plural'), 'url' => $page->listingUrl()], ['label' => $event->title]]" />

    <div class="container page-body with-aside">
        <article class="min-w-0">
            @if ($body = $page->body())<div class="prose">{!! $body !!}</div>@endif

            @if ($speakers->isNotEmpty())
                <h2 class="mt-12 mb-5 text-2xl">{{ __('events::events.sections.speakers') }}</h2>
                <div class="grid-cards grid-cards--sm">
                    @foreach ($speakers as $speaker)
                        <div class="card person">
                            @if ($photo = $speaker->photoUrl())<img src="{{ $photo }}" alt="" loading="lazy">@else<span class="person__avatar"></span>@endif
                            <span><span class="person__name block">{{ $speaker->name() }}</span><span class="person__role block">{{ $speaker->roleLabel }}</span></span>
                        </div>
                    @endforeach
                </div>
            @endif

            @foreach ($page->tabs() as $tab)
                <section class="mt-12">
                    <h2 class="mb-5 text-2xl">{{ $tab['label'] }}</h2>
                    {!! $tab['html'] !!}
                </section>
            @endforeach
        </article>

        <aside class="aside-stack">
            <div class="card aside-card">
                <dl class="fact-list">
                    @foreach ($page->details() as $detail)
                        <div><dt>{{ $detail['label'] }}</dt><dd>{{ $detail['value'] }}</dd></div>
                    @endforeach
                </dl>
                <div class="mt-6 grid gap-2">
                    @if ($page->actions()['register'])
                        <a href="{{ $page->actions()['register'] }}" class="btn btn--primary" target="_blank" rel="noopener">{{ __('events::events.register') }}</a>
                        @if ($page->actions()['register_until'])<p class="muted text-sm">{{ __('events::events.register_until', ['date' => $page->actions()['register_until']]) }}</p>@endif
                    @elseif ($page->actions()['closed'])
                        <span class="badge badge--muted">{{ __('events::events.registration_closed') }}</span>
                    @endif
                    @if ($page->actions()['online'])
                        <a href="{{ $page->actions()['online'] }}" class="btn" target="_blank" rel="noopener">{{ __('events::events.join_online') }}</a>
                    @endif
                    @if ($page->actions()['ics'])
                        <a href="{{ $page->actions()['ics'] }}" class="btn"><x-theme::icon name="calendar" width="16" height="16" />{{ __('events::events.add_to_calendar') }}</a>
                    @endif
                </div>
            </div>
        </aside>
    </div>
</x-theme::layout>
