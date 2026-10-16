{{-- Homepage section "Photo Gallery" (FR-GAL-07). Contract: $items (Album list), $section, $settings (display: albums / photos, photos); $context from the page --}}
@if ($items->isNotEmpty())
    @php($base = isset($context) && $context->isMicrosite() ? rtrim((string) app(\Platform\Context\MicrositeResolver::class)->urlFor($context->entity), '/') : '')
    @if (($settings['display'] ?? 'albums') === 'photos')
        @php($photos = $items->flatMap(fn ($album) => $album->entries('image'))->take(max(1, (int) ($settings['photos'] ?? 12)))->values())
        @include('gallery::partials.photos', ['photos' => $photos, 'layout' => 'grid'])
    @else
        <div style="display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(240px,1fr))">
            @foreach ($items as $album)
                @include('gallery::partials.card', ['album' => $album, 'base' => $base, 'layout' => 'cards'])
            @endforeach
        </div>
    @endif
    <p style="margin-top:var(--space-4, 16px)"><a href="{{ url($base.'/'.\Modules\Gallery\Models\Album::PATH.'?kind=photos') }}" class="button">{{ __('gallery::gallery.all_albums') }}</a></p>
@endif
