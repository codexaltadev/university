{{--
    Search page (FR-SRCH-02/06, UI/UX Part B §12.5): a big search box, chips per module, the results as rows.
    Contract: $results [query, total, groups: slug => [label, count], items: paginator of [title, excerpt, url, module, label]],
    $module, $scope, $scopeLabel, $shortcuts ([label, url])
--}}
<x-theme::layout :title="__('platform::search.title')">
    <section class="page-hero">
        <div class="container max-w-3xl">
            <h1 class="page-hero__title">{{ __('platform::search.title') }}</h1>
            <form action="{{ route('search') }}" method="get" role="search" class="filters">
                <label for="search-q" class="sr-only">{{ __('platform::search.label') }}</label>
                <input id="search-q" type="search" name="q" value="{{ $results['query'] }}" maxlength="100" autofocus placeholder="{{ __('platform::search.placeholder') }}">
                @if ($scope)<input type="hidden" name="in" value="{{ $scope }}">@endif
                <button type="submit" class="btn btn--primary">{{ __('platform::search.button') }}</button>
            </form>
            @if ($scope)
                <p class="muted mt-3">{{ __('platform::search.only_in', ['name' => $scopeLabel]) }} · <a href="{{ route('search', array_filter(['q' => $results['query'], 'module' => $module])) }}" class="text-primary">{{ __('platform::search.everywhere') }}</a></p>
            @endif
            @if ($results['groups'] !== [])
                <nav class="filters" aria-label="{{ __('platform::search.filter') }}">
                    <a href="{{ route('search', array_filter(['q' => $results['query'], 'in' => $scope])) }}" @if (! $module) aria-current="page" @endif class="chip">{{ __('platform::search.all') }} <span>{{ $results['total'] }}</span></a>
                    @foreach ($results['groups'] as $slug => $group)
                        <a href="{{ route('search', array_filter(['q' => $results['query'], 'module' => $slug, 'in' => $scope])) }}" @if ($module === $slug) aria-current="page" @endif class="chip">{{ $group['label'] }} <span>{{ $group['count'] }}</span></a>
                    @endforeach
                </nav>
            @endif
        </div>
    </section>

    <div class="container page-body max-w-3xl">
        @if ($results['query'] !== '')
            <p aria-live="polite" class="muted mb-6">{{ trans_choice('platform::search.count', $results['total'], ['count' => $results['total'], 'query' => $results['query']]) }}</p>
        @endif
        @if (($shortcuts ?? []) !== [])
            <div class="mb-8 flex flex-wrap gap-2">
                @foreach ($shortcuts as $link)<a href="{{ $link['url'] }}" class="btn btn--primary">{{ $link['label'] }}</a>@endforeach
            </div>
        @endif
        <ol class="row-list">
            @foreach ($results['items'] as $item)
                <li class="block">
                    <span class="badge">{{ $item['label'] }}</span>
                    <h2 class="mt-2 text-xl"><a href="{{ $item['url'] }}" class="stretched">{{ $item['title'] }}</a></h2>
                    @if ((string) $item['excerpt'] !== '')<p class="muted mt-1">{{ $item['excerpt'] }}</p>@endif
                </li>
            @endforeach
        </ol>
        @if ($results['query'] !== '' && $results['total'] === 0)
            <p class="empty-state">{{ __('platform::search.none') }}</p>
        @endif
        {{ $results['items']->links() }}
    </div>
</x-theme::layout>
