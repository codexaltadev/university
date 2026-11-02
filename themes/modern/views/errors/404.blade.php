{{-- Not found (UI/UX Part B §12.5): the number, one line, search and home --}}
<x-theme::layout :title="__('theme::theme.not_found')">
    <div class="container error-page">
        <span class="error-page__code" aria-hidden="true">404</span>
        <h1 class="mt-4 text-4xl">{{ __('theme::theme.not_found') }}</h1>
        <p class="lead mx-auto mt-3 max-w-xl">{{ __('theme::theme.not_found_text') }}</p>
        <form action="{{ route('search') }}" method="get" role="search">
            <label for="error-search" class="sr-only">{{ __('theme::theme.search') }}</label>
            <input id="error-search" type="search" name="q" maxlength="100" placeholder="{{ __('theme::theme.search_placeholder') }}">
            <button type="submit" class="btn btn--primary">{{ __('theme::theme.search') }}</button>
        </form>
        <p class="mt-6"><a href="{{ url('/') }}" class="btn">{{ __('theme::theme.go_home') }}</a></p>
    </div>
</x-theme::layout>
