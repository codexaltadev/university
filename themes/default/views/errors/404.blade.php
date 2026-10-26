{{--
    Page not found (404) in DIU's look (the card of DIU's not-found page, with the menus around it so the visitor can go on):
    "404", the title and line, a search field, Go Back and Home. Contract: none (the kernel's errors/404 includes it)
--}}
<x-theme::layout :title="__('theme::theme.errors.not_found')">
    <div class="bg-gray-50 flex items-center justify-center px-4 pt-32 pb-20 min-h-[70vh]" style="margin-top: calc(-1 * var(--header-h, 0px))">
        <div class="max-w-2xl w-full text-center">
            <p class="text-8xl md:text-9xl font-extrabold tracking-tight text-primary/15 select-none" aria-hidden="true">404</p>
            <h1 class="-mt-6 text-4xl md:text-5xl font-bold mb-4 tracking-tight text-gray-900">{{ __('theme::theme.errors.not_found') }}</h1>
            <p class="text-lg text-gray-600 mb-10 max-w-lg mx-auto leading-relaxed">{{ __('theme::theme.errors.not_found_text') }}</p>

            <form action="{{ route('search') }}" method="get" role="search" class="relative max-w-xl mx-auto mb-8">
                <label for="not-found-q" class="sr-only">{{ __('platform::search.label') }}</label>
                <x-theme::icon name="search" class="absolute left-4 top-1/2 -translate-y-1/2 size-5 text-gray-400 pointer-events-none" />
                <input id="not-found-q" type="search" name="q" maxlength="100" placeholder="{{ __('theme::theme.search_placeholder') }}"
                       class="w-full rounded-xl border-2 border-gray-200 bg-white pl-12 pr-28 py-3.5 text-base outline-none focus:border-blue-500">
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg bg-primary px-4 py-2 font-medium text-white hover:bg-secondary cursor-pointer">{{ __('platform::search.button') }}</button>
            </form>

            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <button type="button" onclick="history.length > 1 ? history.back() : (location.href = '/')"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-white border-2 border-gray-200 text-gray-700 rounded-xl font-medium hover:bg-gray-50 hover:border-gray-300 cursor-pointer">
                    <x-theme::icon name="arrow-left" class="w-5 h-5" /> {{ __('theme::theme.errors.back') }}
                </button>
                <a href="{{ url('/') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 shadow-sm hover:no-underline">
                    <x-theme::icon name="home" class="w-5 h-5" /> {{ __('theme::theme.home') }}
                </a>
            </div>
        </div>
    </div>
</x-theme::layout>
