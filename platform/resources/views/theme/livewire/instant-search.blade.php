{{--
    Header search (FR-SRCH-05), combobox pattern. Themes may override it
    (views/livewire/instant-search.blade.php). Contract: wire:model "q", $results (or null), $allUrl.
--}}
<div class="instant-search" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false" style="position:relative">
    <form action="{{ route('search') }}" method="get" role="search">
        <label for="instant-search-q" style="position:absolute;left:-9999px">{{ __('platform::search.label') }}</label>
        <input id="instant-search-q" type="search" name="q" autocomplete="off" maxlength="100"
               wire:model.live.debounce.300ms="q" @focus="open = true" @input="open = true"
               role="combobox" aria-controls="instant-search-list" :aria-expanded="open"
               placeholder="{{ __('platform::search.placeholder') }}"
               style="padding:var(--space-2, 8px) var(--space-3, 12px);border:1px solid var(--color-border, #ccc);border-radius:var(--radius-md, 8px);font:inherit;min-width:14rem">
        @if ($scope)
            <input type="hidden" name="in" value="{{ $scope }}">
        @endif
    </form>

    @if ($results)
        <div id="instant-search-list" role="listbox" x-show="open" x-cloak
             style="position:absolute;right:0;top:100%;z-index:60;width:min(26rem, 90vw);background:var(--color-bg, #fff);border:1px solid var(--color-border, #ddd);border-radius:var(--radius-md, 8px);box-shadow:var(--shadow-card);padding:var(--space-2, 8px)">
            @forelse ($results['items'] as $item)
                <a role="option" href="{{ $item['url'] }}" style="display:block;padding:var(--space-2, 8px);text-decoration:none;color:inherit;border-radius:var(--radius-sm, 4px)">
                    <span style="display:block;font-size:var(--text-xs, 14px);color:var(--color-text-muted, #666)">{{ $item['label'] }}</span>
                    {{ $item['title'] }}
                </a>
            @empty
                <p style="margin:var(--space-2, 8px)">{{ __('platform::search.none') }}</p>
            @endforelse
            @if ($results['total'] > count($results['items']->items()))
                <a href="{{ $allUrl }}" style="display:block;padding:var(--space-2, 8px);font-weight:600">{{ __('platform::search.all_results', ['count' => $results['total']]) }}</a>
            @endif
        </div>
    @endif
</div>
