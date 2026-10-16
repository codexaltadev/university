{{-- "News" page block. Contract: $data (categories, posts, layout: grid / list, limit), $locale --}}
@php($posts = \Modules\News\Blocks\NewsBlock::posts((array) $data))
@if ($posts->isNotEmpty())
    <div class="block block--news" style="display:grid;gap:var(--space-4, 16px);grid-template-columns:{{ ($data['layout'] ?? 'grid') === 'list' ? '1fr' : 'repeat(auto-fill,minmax(240px,1fr))' }}">
        @foreach ($posts as $post)
            @include('news::partials.card', ['post' => $post, 'layout' => ($data['layout'] ?? 'grid') === 'list' ? 'list' : 'grid', 'base' => ''])
        @endforeach
    </div>
@endif
