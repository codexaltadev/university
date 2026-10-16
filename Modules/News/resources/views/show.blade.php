{{--
    /news/{slug} (FR-NEWS-02/03). Contract: $post, $related (posts of the same place), $layout (standard / full_width),
    $base (place path or '')
--}}
<x-theme::layout :title="$post->title">
    @php($image = $post->featuredImage())
    <article class="news-post" style="padding-block:var(--space-8, 32px)">
        <div class="container" style="{{ $layout === 'full_width' ? '' : 'max-width:860px' }}">
            <nav class="breadcrumb" aria-label="{{ __('platform::relations.breadcrumb') }}">
                <ol>
                    <li><a href="{{ url($base ?: '/') }}">{{ __('platform::relations.home') }}</a><span aria-hidden="true"> › </span></li>
                    <li><a href="{{ url($base.'/'.\Modules\News\Models\Post::PATH) }}">{{ __('news::news.plural') }}</a><span aria-hidden="true"> › </span></li>
                    <li><span aria-current="page">{{ $post->title }}</span></li>
                </ol>
            </nav>

            <h1>{{ $post->title }}</h1>
            @if (filled($post->subtitle))<p class="lead">{{ $post->subtitle }}</p>@endif
            <p style="color:var(--color-muted, #6b7280)">
                <time datetime="{{ $post->shownAt()?->toIso8601String() }}">{{ $post->shownAt()?->translatedFormat('j F Y') }}</time>
                @if (settings('news.show_author', true) && ($author = $post->authorName())) · {{ $author }}@endif
                @foreach ($post->termsIn(\Modules\News\Models\Post::CATEGORY) as $term)
                    · <a href="{{ url($base.'/'.\Modules\News\Models\Post::PATH).'?category='.$term->slug() }}">{{ $term->name }}</a>
                @endforeach
            </p>
        </div>

        @if ($image)
            <div class="container" style="{{ $layout === 'full_width' ? '' : 'max-width:860px' }}">
                <img src="{{ $image->url('large') }}" alt="{{ $image->alt ?? '' }}" style="width:100%;max-height:520px;object-fit:cover;border-radius:var(--radius-md, 8px);margin-block:var(--space-4, 16px)">
            </div>
        @endif

        <div class="container" style="{{ $layout === 'full_width' ? '' : 'max-width:860px' }}">
            @if (filled($post->body))
                <div class="prose">{!! \Illuminate\Support\Str::sanitizeHtml((string) $post->body) !!}</div>
            @endif

            @if ($embed = $post->videoEmbed())
                <div style="position:relative;padding-top:56.25%;margin-top:var(--space-6, 24px)">
                    <iframe src="{{ $embed }}" title="{{ __('news::news.fields.video_url') }}" loading="lazy" allowfullscreen style="position:absolute;inset:0;width:100%;height:100%;border:0"></iframe>
                </div>
            @endif

            @if (($gallery = $post->galleryImages())->isNotEmpty())
                <section style="margin-top:var(--space-8, 32px)">
                    <h2>{{ __('news::news.fields.gallery') }}</h2>
                    <div style="display:grid;gap:var(--space-3, 12px);grid-template-columns:repeat(auto-fill,minmax(180px,1fr))">
                        @foreach ($gallery as $item)
                            <a href="{{ $item->url('large') }}" target="_blank" rel="noopener"><img src="{{ $item->url('medium') }}" alt="{{ $item->alt ?? '' }}" loading="lazy" style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:var(--radius-sm, 4px)"></a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if (($tags = $post->termsIn(\Platform\Taxonomy\Vocabularies::SHARED_TAGS))->isNotEmpty())
                <p style="margin-top:var(--space-6, 24px);display:flex;flex-wrap:wrap;gap:var(--space-2, 8px)">
                    @foreach ($tags as $tag)<a class="chip" href="{{ url($base.'/'.\Modules\News\Models\Post::PATH).'?tag='.$tag->slug() }}">#{{ $tag->name }}</a>@endforeach
                </p>
            @endif
        </div>

        @if ($related->isNotEmpty())
            <section class="container" style="margin-top:var(--space-10, 40px)">
                <h2>{{ __('news::news.related') }}</h2>
                <div style="display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(240px,1fr))">
                    @foreach ($related as $item)
                        @include('news::partials.card', ['post' => $item, 'layout' => 'grid', 'base' => $base])
                    @endforeach
                </div>
            </section>
        @endif
    </article>
</x-theme::layout>
