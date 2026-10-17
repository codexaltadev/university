{{--
    Header search (FR-SRCH-05), combobox pattern — the default theme's look of the kernel view.
    Contract: wire:model "q", $results (or null), $allUrl, $scope.
--}}
<div class="instant-search" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
    <form action="{{ route('search') }}" method="get" role="search" class="instant-search__form">
        <label for="instant-search-q" class="sr-only">{{ __('platform::search.label') }}</label>
        <x-theme::icon name="search" class="instant-search__icon" />
        <input id="instant-search-q" type="search" name="q" autocomplete="off" maxlength="100"
               wire:model.live.debounce.300ms="q" @focus="open = true" @input="open = true"
               role="combobox" aria-controls="instant-search-list" :aria-expanded="open"
               placeholder="{{ __('platform::search.placeholder') }}" class="instant-search__input">
        @if ($scope)
            <input type="hidden" name="in" value="{{ $scope }}">
        @endif
    </form>

    @if ($results)
        <div id="instant-search-list" role="listbox" x-show="open" x-cloak class="instant-search__list">
            @forelse ($results['items'] as $item)
                <a role="option" href="{{ $item['url'] }}" class="instant-search__item">
                    <span class="instant-search__label">{{ $item['label'] }}</span>
                    {{ $item['title'] }}
                </a>
            @empty
                <p class="instant-search__none">{{ __('platform::search.none') }}</p>
            @endforelse
            @if ($results['total'] > count($results['items']->items()))
                <a href="{{ $allUrl }}" class="instant-search__all">{{ __('platform::search.all_results', ['count' => $results['total']]) }}</a>
            @endif
        </div>
    @endif
</div>
