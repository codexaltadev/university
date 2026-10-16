{{-- Homepage section "News by Category" (FR-NEWS-04). Contract: $items (list of [category: Term, posts]), $section, $settings; $context from the page --}}
@if ($items->isNotEmpty())
    @php($base = isset($context) && $context->isMicrosite() ? rtrim((string) app(\Platform\Context\MicrositeResolver::class)->urlFor($context->entity), '/') : '')
    <div class="news-by-category" style="display:grid;gap:var(--space-6, 24px);grid-template-columns:repeat(auto-fit,minmax(280px,1fr))">
        @foreach ($items as $group)
            <section>
                <h3><a href="{{ url($base.'/'.\Modules\News\Models\Post::PATH).'?category='.$group['category']->slug() }}">{{ $group['category']->name }}</a></h3>
                <div style="display:grid;gap:var(--space-3, 12px)">
                    @foreach ($group['posts'] as $post)
                        @include('news::partials.card', ['post' => $post, 'layout' => 'list', 'base' => $base])
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
@endif
