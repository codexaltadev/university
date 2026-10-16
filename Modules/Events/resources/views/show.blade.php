{{--
    /events/{slug} (FR-EVT-04..10). Contract: $event, $speakers (PersonView list: speakers, moderators), $gallery (MediaItem list),
    $base (place path or '')
--}}
<x-theme::layout :title="$event->title">
    @php($state = $event->state())
    @php($image = $event->firstMedia('featured_image'))
    <article class="event" style="padding-block:var(--space-8, 32px)">
        <div class="container" style="max-width:900px">
            <nav class="breadcrumb" aria-label="{{ __('platform::relations.breadcrumb') }}">
                <ol>
                    <li><a href="{{ url($base ?: '/') }}">{{ __('platform::relations.home') }}</a><span aria-hidden="true"> › </span></li>
                    <li><a href="{{ url($base.'/'.\Modules\Events\Models\Event::PATH) }}">{{ __('events::events.plural') }}</a><span aria-hidden="true"> › </span></li>
                    <li><span aria-current="page">{{ $event->title }}</span></li>
                </ol>
            </nav>

            <h1>{{ $event->title }} <span class="badge {{ $state === 'past' ? 'badge-muted' : 'badge-success' }}">{{ __("events::events.states.{$state}") }}</span></h1>
            <ul style="list-style:none;padding:0;display:grid;gap:4px">
                <li><strong>{{ __('events::events.fields.when') }}:</strong> {{ $event->when() }}</li>
                @if (filled($event->venue))<li><strong>{{ __('events::events.fields.venue') }}:</strong> {{ $event->venue }}</li>@endif
                @if (filled($event->organizer_label))<li><strong>{{ __('events::events.fields.organizer') }}:</strong> {{ $event->organizer_label }}</li>@endif
                @if (filled($event->online_url) && $state !== 'past' && preg_match('#^https?://#i', $event->online_url))<li><strong>{{ __('events::events.fields.online_url') }}:</strong> <a href="{{ $event->online_url }}" target="_blank" rel="noopener">{{ __('events::events.join_online') }}</a></li>@endif
            </ul>

            <p style="display:flex;flex-wrap:wrap;gap:var(--space-3, 12px)">
                @if ($event->registrationOpen())
                    <a href="{{ $event->registration_url }}" class="button button-primary" target="_blank" rel="noopener">{{ __('events::events.register') }}</a>
                    @if ($event->registration_ends_at)<span style="align-self:center">{{ __('events::events.register_until', ['date' => $event->registration_ends_at->translatedFormat('j F Y, H:i')]) }}</span>@endif
                @elseif (filled($event->registration_url))
                    <span class="badge badge-muted" style="align-self:center">{{ __('events::events.registration_closed') }}</span>
                @endif
                @if ($state !== 'past')
                    <a href="{{ url($base.'/'.\Modules\Events\Models\Event::PATH.'/'.$event->slug().'.ics') }}" class="button">{{ __('events::events.add_to_calendar') }}</a>
                    <a href="{{ $event->googleCalendarUrl() }}" class="button" target="_blank" rel="noopener">Google Calendar</a>
                @endif
            </p>

            @if ($image)
                <img src="{{ $image->url('large') }}" alt="{{ $image->alt ?? '' }}" style="width:100%;max-height:480px;object-fit:cover;border-radius:var(--radius-md, 8px);margin-block:var(--space-4, 16px)">
            @endif
            @if (filled($event->summary))<p class="lead">{{ $event->summary }}</p>@endif
            @if (filled($event->body))<div class="prose">{!! \Illuminate\Support\Str::sanitizeHtml((string) $event->body) !!}</div>@endif

            @if ($speakers->isNotEmpty())
                <section style="margin-top:var(--space-8, 32px)">
                    <h2>{{ __('events::events.sections.speakers') }}</h2>
                    <div style="display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(200px,1fr))">
                        @foreach ($speakers as $person)
                            <div style="display:flex;gap:var(--space-3, 12px);align-items:center">
                                @if ($photo = $person->photoUrl())<img src="{{ $photo }}" alt="" style="width:56px;height:56px;border-radius:50%;object-fit:cover">@endif
                                <div>
                                    <strong>@if ($url = $person->profileUrl())<a href="{{ $url }}">{{ $person->name() }}</a>@else{{ $person->name() }}@endif</strong><br>
                                    <small>{{ $person->roleLabel }}@if ($person->designation()) · {{ $person->designation() }}@endif</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($gallery->isNotEmpty())
                <section style="margin-top:var(--space-8, 32px)">
                    <h2>{{ __('events::events.fields.gallery') }}</h2>
                    <div style="display:grid;gap:var(--space-3, 12px);grid-template-columns:repeat(auto-fill,minmax(180px,1fr))">
                        @foreach ($gallery as $item)
                            <a href="{{ $item->url('large') }}" target="_blank" rel="noopener"><img src="{{ $item->url('medium') }}" alt="{{ $item->alt ?? '' }}" loading="lazy" style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:var(--radius-sm, 4px)"></a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </article>
</x-theme::layout>
