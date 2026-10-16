{{--
    /gallery and /{entity}/gallery (FR-GAL-05). Contract: $albums (paginator, newest first), $categories (top level), $category,
    $kind (photos / videos / null), $layout (cards / grid), $base (microsite path or '')
--}}
<x-theme::layout :title="__('gallery::gallery.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('gallery::gallery.plural') }}</h1>

        <nav style="display:flex;flex-wrap:wrap;gap:var(--space-2, 8px);margin-top:var(--space-4, 16px)">
            @foreach ([null => __('gallery::gallery.all'), 'photos' => __('gallery::gallery.photos'), 'videos' => __('gallery::gallery.videos')] as $k => $label)
                <a href="{{ url($base.'/gallery').'?'.http_build_query(array_filter(['kind' => $k ?: null, 'category' => request('category'), 'view' => request('view')])) }}" @class(['button', 'button--outline' => $kind !== ($k ?: null)])>{{ $label }}</a>
            @endforeach
        </nav>

        @if ($categories->isNotEmpty())
            <form method="get" style="margin-top:var(--space-3, 12px)">
                <label>{{ __('gallery::gallery.fields.category') }}
                    <select name="category" onchange="this.form.submit()">
                        <option value="">{{ __('gallery::gallery.all') }}</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->slug() }}" @selected($category?->is($c))>{{ $c->name }}</option>
                            @foreach ($c->children ?? [] as $child)
                                <option value="{{ $child->slug() }}" @selected($category?->is($child))>— {{ $child->name }}</option>
                            @endforeach
                        @endforeach
                    </select>
                </label>
                @if ($kind)<input type="hidden" name="kind" value="{{ $kind }}">@endif
                <noscript><button type="submit" class="button">{{ __('gallery::gallery.filter') }}</button></noscript>
            </form>
        @endif

        @if ($albums->isEmpty())
            <p style="margin-top:var(--space-8, 32px)">{{ __('gallery::gallery.none') }}</p>
        @else
            <div style="display:grid;gap:var(--space-4, 16px);margin-top:var(--space-6, 24px);grid-template-columns:repeat(auto-fill,minmax({{ $layout === 'grid' ? '180px' : '260px' }},1fr))">
                @foreach ($albums as $album)
                    @include('gallery::partials.card', ['album' => $album, 'base' => $base, 'layout' => $layout])
                @endforeach
            </div>
            <div style="margin-top:var(--space-6, 24px)">{{ $albums->links() }}</div>
        @endif
    </div>
</x-theme::layout>
