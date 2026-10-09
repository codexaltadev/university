{{--
    Search page (FR-SRCH-02/06). Themes may override it (views/search.blade.php).
    Contract: $results = [query, total, groups: slug => [label, count], items: paginator of
    [title, excerpt (highlighted HTML), url, module, label]], $module, $scope, $scopeLabel.
--}}
<x-theme::layout :title="__('platform::search.title')">
    <div class="container search-page" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('platform::search.title') }}</h1>

        <form action="{{ route('search') }}" method="get" role="search" class="search-page__form" style="display:flex;gap:var(--space-2, 8px);margin-block:var(--space-4, 16px)">
            <label for="search-q" class="sr-only" style="position:absolute;left:-9999px">{{ __('platform::search.label') }}</label>
            <input id="search-q" type="search" name="q" value="{{ $results['query'] }}" maxlength="100" autofocus
                   placeholder="{{ __('platform::search.placeholder') }}"
                   style="flex:1;padding:var(--space-3, 12px);border:1px solid var(--color-border, #ccc);border-radius:var(--radius-md, 8px);font:inherit">
            @if ($scope)
                <input type="hidden" name="in" value="{{ $scope }}">
            @endif
            <button type="submit" class="btn btn--primary">{{ __('platform::search.button') }}</button>
        </form>

        @if ($scope)
            <p class="search-page__scope">
                {{ __('platform::search.only_in', ['name' => $scopeLabel]) }}
                · <a href="{{ route('search', array_filter(['q' => $results['query'], 'module' => $module])) }}">{{ __('platform::search.everywhere') }}</a>
            </p>
        @endif

        @if ($results['query'] !== '')
            <p aria-live="polite" class="search-page__count">{{ trans_choice('platform::search.count', $results['total'], ['count' => $results['total'], 'query' => $results['query']]) }}</p>
        @endif

        @if ($results['groups'] !== [])
            <nav class="search-page__groups" aria-label="{{ __('platform::search.filter') }}" style="display:flex;flex-wrap:wrap;gap:var(--space-2, 8px);margin-block:var(--space-4, 16px)">
                <a href="{{ route('search', array_filter(['q' => $results['query'], 'in' => $scope])) }}" @if (! $module) aria-current="true" @endif class="chip">{{ __('platform::search.all') }} ({{ $results['total'] }})</a>
                @foreach ($results['groups'] as $slug => $group)
                    <a href="{{ route('search', array_filter(['q' => $results['query'], 'module' => $slug, 'in' => $scope])) }}" @if ($module === $slug) aria-current="true" @endif class="chip">{{ $group['label'] }} ({{ $group['count'] }})</a>
                @endforeach
            </nav>
        @endif

        <ol class="search-page__results" style="list-style:none;padding:0;display:grid;gap:var(--space-6, 24px)">
            @foreach ($results['items'] as $item)
                <li class="search-result">
                    <span class="search-result__module" style="font-size:var(--text-xs, 14px);color:var(--color-text-muted, #666)">{{ $item['label'] }}</span>
                    <h2 style="margin:var(--space-1, 4px) 0;font-size:var(--text-lg, 20px)"><a href="{{ $item['url'] }}">{{ $item['title'] }}</a></h2>
                    @if ((string) $item['excerpt'] !== '')
                        <p style="margin:0">{{ $item['excerpt'] }}</p>
                    @endif
                </li>
            @endforeach
        </ol>

        @if ($results['query'] !== '' && $results['total'] === 0)
            <p>{{ __('platform::search.none') }}</p>
        @endif

        {{ $results['items']->links() }}
    </div>
</x-theme::layout>
