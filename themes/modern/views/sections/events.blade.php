{{-- Upcoming events (theme section, source events.upcoming): date cards. Contract: $section (title, subheading, content: eyebrow, button_label, button_url), $items (Event) --}}
<div class="container">
    <x-theme::section-head :eyebrow="$section->content['eyebrow'] ?? null" :title="$section->title ?: __('theme::theme.sections.events')" :sub="$section->subheading"
                           :link="$section->content['button_url'] ?? null" :link-label="$section->content['button_label'] ?? null" />
    @if (collect($items ?? [])->isEmpty())
        <p class="empty-state">{{ __('theme::theme.sections.nothing_yet') }}</p>
    @else
        <div class="grid-cards">
            @foreach ($items as $event)
                <article class="card card--link flex gap-4">
                    <span class="date-box"><b>{{ $event->starts_at?->translatedFormat('d') }}</b>{{ $event->starts_at?->translatedFormat('M') }}</span>
                    <div class="min-w-0">
                        <h3 class="card__title text-lg"><a href="{{ url($event->url()) }}" class="stretched">{{ $event->title }}</a></h3>
                        <p class="card__meta pt-1">{{ $event->when() }}@if ($event->venue) · {{ $event->venue }}@endif</p>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
