{{-- Photo gallery (theme section, source gallery.photos): albums as a mosaic, the first one large. Contract: $section (title, subheading, content: eyebrow, button_label, button_url), $items (Album) --}}
@if (collect($items ?? [])->isNotEmpty())
    <div class="container">
        <x-theme::section-head :eyebrow="$section->content['eyebrow'] ?? null" :title="$section->title ?: __('theme::theme.sections.gallery')" :sub="$section->subheading"
                               :link="$section->content['button_url'] ?? null" :link-label="$section->content['button_label'] ?? null" />
        <div class="gallery-grid">
            @foreach ($items->take(5) as $album)
                <a href="{{ url($album->url()) }}">
                    @if ($cover = $album->coverUrl($loop->first ? 'large' : 'medium'))<img src="{{ $cover }}" alt="" loading="lazy">@endif
                    <figcaption>{{ $album->title }}</figcaption>
                </a>
            @endforeach
        </div>
    </div>
@endif
