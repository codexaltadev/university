{{-- News (theme section, source news.latest): picture cards. Contract: $section (title, subheading, content: eyebrow, button_label, button_url), $items (Post) --}}
<div class="container">
    <x-theme::section-head :eyebrow="$section->content['eyebrow'] ?? null" :title="$section->title ?: __('theme::theme.sections.news')" :sub="$section->subheading"
                           :link="$section->content['button_url'] ?? null" :link-label="$section->content['button_label'] ?? null" />
    @if (collect($items ?? [])->isEmpty())
        <p class="empty-state">{{ __('theme::theme.sections.nothing_yet') }}</p>
    @else
        <div class="grid-cards">
            @foreach ($items as $post)
                <article class="card card--media card--link">
                    <div class="card__media">
                        @if ($image = $post->featuredImage())<img src="{{ $image->url('medium') }}" alt="{{ $image->alt_text }}" loading="lazy">@endif
                    </div>
                    <div class="card__body">
                        <h3 class="card__title"><a href="{{ url($post->url()) }}" class="stretched">{{ $post->title }}</a></h3>
                        @if ($post->excerpt)<p class="muted line-clamp-2">{{ $post->excerpt }}</p>@endif
                        <p class="card__meta">{{ $post->active_at?->translatedFormat('d M Y') }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
