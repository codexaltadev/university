{{-- Rankings and achievements (theme section, source achievements.recent). Contract: $section (title, subheading, content: eyebrow, button_label, button_url), $items (Achievement) --}}
@if (collect($items ?? [])->isNotEmpty())
    <div class="container">
        <x-theme::section-head :eyebrow="$section->content['eyebrow'] ?? null" :title="$section->title ?: __('theme::theme.sections.achievements')" :sub="$section->subheading"
                               :link="$section->content['button_url'] ?? null" :link-label="$section->content['button_label'] ?? null" />
        <div class="grid-cards">
            @foreach ($items as $achievement)
                <article class="card card--link flex flex-col gap-3">
                    @if ($image = $achievement->firstMedia('image'))
                        <img src="{{ $image->url('thumb') }}" alt="" class="size-14 rounded-md object-cover" loading="lazy">
                    @else
                        <span class="tile__icon"><x-theme::icon name="award" /></span>
                    @endif
                    <h3 class="card__title text-lg"><a href="{{ url($achievement->url()) }}" class="stretched">{{ $achievement->title }}</a></h3>
                    <p class="card__meta">{{ collect([$achievement->position_label, $achievement->achieved_on?->translatedFormat('M Y')])->filter()->implode(' · ') }}</p>
                </article>
            @endforeach
        </div>
    </div>
@endif
