{{-- Search panel under the header (FR-SRCH-05): the website search page --}}
<div id="site-search" class="search-panel" x-show="search" x-cloak x-transition.opacity>
    <div class="container">
        <form action="{{ route('search') }}" method="get" role="search">
            <label for="site-search-q" class="sr-only">{{ __('theme::theme.search') }}</label>
            <input id="site-search-q" x-ref="searchInput" type="search" name="q" maxlength="100" placeholder="{{ __('theme::theme.search_placeholder') }}">
            <button type="submit" class="btn btn--primary">{{ __('theme::theme.search') }}</button>
        </form>
    </div>
</div>
