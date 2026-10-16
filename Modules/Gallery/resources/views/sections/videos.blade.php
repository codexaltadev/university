{{-- Homepage section "Video Gallery" (FR-GAL-07). Contract: $items (Video list), $section, $settings (series, count); $context from the page --}}
@if ($items->isNotEmpty())
    @php($base = isset($context) && $context->isMicrosite() ? rtrim((string) app(\Platform\Context\MicrositeResolver::class)->urlFor($context->entity), '/') : '')
    <div style="display:grid;gap:var(--space-5, 20px);grid-template-columns:repeat(auto-fill,minmax(300px,1fr))">
        @foreach ($items as $video)
            @include('gallery::partials.video', ['video' => $video])
        @endforeach
    </div>
    <p style="margin-top:var(--space-4, 16px)"><a href="{{ url($base.'/'.\Modules\Gallery\Models\Album::PATH.'?kind=videos') }}" class="button">{{ __('gallery::gallery.all_videos') }}</a></p>
@endif
