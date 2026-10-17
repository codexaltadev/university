{{--
    /news and /{entity}/news (FR-NEWS-03/05). Contract: $posts (paginator), $categories (top-level, in order), $category (?Term),
    $tag (?Term), $archive (list of {year, month, total}), $layout (grid / list / cards / magazine), $base (place path or '')
--}}
<x-theme::layout :title="__('news::news.plural')">
    @php($listing = url($base.'/'.\Modules\News\Models\Post::PATH))
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('news::news.plural') }}@if ($category) — {{ $category->name }}@elseif ($tag) — #{{ $tag->name }}@endif
            @if (request('year')) <small>({{ request('month') ? \Illuminate\Support\Carbon::create((int) request('year'), (int) request('month'))->translatedFormat('F Y') : request('year') }})</small>@endif
        </h1>

        <div style="display:grid;gap:var(--space-8, 32px);grid-template-columns:minmax(0,1fr);margin-top:var(--space-6, 24px)" class="news-layout">
            <div>
                <form method="get" style="display:flex;gap:var(--space-2, 8px);margin-bottom:var(--space-4, 16px)">
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('news::news.search_placeholder') }}" aria-label="{{ __('news::news.search') }}" style="flex:1">
                    @foreach (['category', 'tag', 'year', 'month', 'view'] as $keep)
                        @if (request($keep))<input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">@endif
                    @endforeach
                    <button type="submit" class="btn">{{ __('news::news.search') }}</button>
                </form>

                @if ($categories->isNotEmpty())
                    <nav aria-label="{{ __('news::news.fields.category') }}" style="display:flex;flex-wrap:wrap;gap:var(--space-2, 8px);margin-bottom:var(--space-6, 24px)">
                        <a href="{{ $listing }}" @class(['chip', 'chip-active' => ! $category])>{{ __('news::news.all') }}</a>
                        @foreach ($categories as $item)
                            <a href="{{ $listing.'?category='.$item->slug() }}" @class(['chip', 'chip-active' => $category?->is($item)])>{{ $item->name }}</a>
                        @endforeach
                    </nav>
                @endif

                @if ($posts->isEmpty())
                    <p>{{ __('news::news.none') }}</p>
                @elseif ($layout === 'magazine')
                    @php($lead = $posts->first())
                    <div style="display:grid;gap:var(--space-6, 24px)">
                        @include('news::partials.card', ['post' => $lead, 'layout' => 'cards', 'base' => $base])
                        <div style="display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(240px,1fr))">
                            @foreach ($posts->slice(1) as $post)
                                @include('news::partials.card', ['post' => $post, 'layout' => 'grid', 'base' => $base])
                            @endforeach
                        </div>
                    </div>
                @else
                    <div style="display:grid;gap:var(--space-4, 16px);grid-template-columns:{{ $layout === 'list' ? '1fr' : 'repeat(auto-fill,minmax('.($layout === 'cards' ? '320px' : '260px').',1fr))' }}">
                        @foreach ($posts as $post)
                            @include('news::partials.card', ['post' => $post, 'layout' => $layout, 'base' => $base])
                        @endforeach
                    </div>
                @endif
                <div style="margin-top:var(--space-6, 24px)">{{ $posts->links() }}</div>
            </div>

            @if ($archive->isNotEmpty())
                <aside aria-label="{{ __('news::news.archive') }}">
                    <h2 style="font-size:1.1em">{{ __('news::news.archive') }}</h2>
                    <ul style="list-style:none;padding:0;display:grid;gap:4px">
                        @foreach ($archive as $row)
                            <li><a href="{{ $listing.'?year='.$row->year.'&month='.$row->month }}">{{ \Illuminate\Support\Carbon::create((int) $row->year, (int) $row->month)->translatedFormat('F Y') }}</a> ({{ $row->total }})</li>
                        @endforeach
                    </ul>
                </aside>
            @endif
        </div>
    </div>
</x-theme::layout>
