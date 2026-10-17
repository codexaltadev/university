{{-- Homepage sections "Latest News" and "Featured News" (FR-NEWS-04). Contract: $items (Post list), $section, $settings (display: grid / list / slider); $context (the homepage's site) from the page --}}
@if ($items->isNotEmpty())
    @php($display = $settings['display'] ?? 'grid')
    @php($base = isset($context) && $context->isMicrosite() ? rtrim((string) app(\Platform\Context\MicrositeResolver::class)->urlFor($context->entity), '/') : '')
    <div class="news-section" @if ($display === 'slider') style="display:grid;grid-auto-flow:column;grid-auto-columns:minmax(280px,1fr);gap:var(--space-4, 16px);overflow-x:auto;scroll-snap-type:x mandatory" @else style="display:grid;gap:var(--space-4, 16px);grid-template-columns:{{ $display === 'list' ? '1fr' : 'repeat(auto-fill,minmax(260px,1fr))' }}" @endif>
        @foreach ($items as $post)
            <div @if ($display === 'slider') style="scroll-snap-align:start" @endif>
                @include('news::partials.card', ['post' => $post, 'layout' => $display === 'list' ? 'list' : 'cards', 'base' => $base])
            </div>
        @endforeach
    </div>
    <p style="margin-top:var(--space-4, 16px)"><a href="{{ url($base.'/'.\Modules\News\Models\Post::PATH) }}" class="btn">{{ __('news::news.all_news') }}</a></p>
@endif
