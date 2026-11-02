{{-- Testimonials (theme section, source testimonials.list): quote cards. Contract: $section (title, subheading, content: eyebrow, button_label, button_url), $items (Testimonial) --}}
@if (collect($items ?? [])->isNotEmpty())
    <div class="container">
        <x-theme::section-head :eyebrow="$section->content['eyebrow'] ?? null" :title="$section->title ?: __('theme::theme.sections.testimonials')" :sub="$section->subheading"
                               :link="$section->content['button_url'] ?? null" :link-label="$section->content['button_label'] ?? null" />
        <div class="grid-cards grid-cards--lg">
            @foreach ($items->take(6) as $testimonial)
                <figure class="card quote-card m-0">
                    <blockquote>{{ str(strip_tags((string) $testimonial->quote))->limit(280) }}</blockquote>
                    <figcaption class="person">
                        @if ($photo = $testimonial->photoUrl())<img src="{{ $photo }}" alt="" loading="lazy">@else<span class="person__avatar"></span>@endif
                        <span>
                            <span class="person__name block">{{ $testimonial->person()?->name() }}</span>
                            <span class="person__role block">{{ $testimonial->person()?->designation() }}</span>
                        </span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
@endif
