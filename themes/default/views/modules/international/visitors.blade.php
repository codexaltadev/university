{{--
    /visitors — guests from home and abroad, in DIU's testimonial look (the page DIU calls "Testimonials"):
    hero; two columns of cards — the start of their words, photo, name, title, country, date,
    "Read more" — opening all of it in a window (#visitor-{id} opens it, the homepage slider links there).
    Contract: $visitors (paginator, newest first), $base, $page (VisitorsPage)
--}}
<x-theme::layout :title="settings('international.visitors_title') ?: __('international::international.visitors')">
    @include('theme::partials.diu.hero', [
        'title' => settings('international.visitors_title') ?: __('international::international.visitors'),
        'subtitle' => e(settings('international.visitors_text')),
        'image' => media_url(settings('international.visitors_image'), 'large') ?? theme_asset('images/testimonials-banner.jpg'),
        'breadcrumbs' => [['label' => __('theme::theme.home'), 'url' => url($base ?: '/')], ['label' => settings('international.visitors_title') ?: __('international::international.visitors'), 'url' => $page->listingUrl()]],
    ])

    <div class="min-h-screen bg-gray-50" x-data="{ open: null }" x-init="if (location.hash.startsWith('#visitor-')) open = location.hash.slice(1)" @keydown.escape.window="open = null">
        <div class="max-w-6xl mx-auto px-4 py-12 sm:px-6 lg:px-8">
            @if (($cards = $page->cards()) === [])
                <p class="text-center text-gray-500 py-12">{{ __('international::international.none') }}</p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
                    @foreach ($cards as $card)
                        <article id="{{ $card['id'] }}" class="group bg-white rounded-lg p-6 shadow-sm border border-gray-200 hover:shadow-md hover:border-gray-300 transition-all duration-200 cursor-pointer flex flex-col"
                                 @click="open = @js($card['id']); history.replaceState(null, '', '#' + open)">
                            <p class="text-gray-700 leading-relaxed mb-6 line-clamp-4 text-sm flex-1">{{ $card['excerpt'] }}</p>
                            <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
                                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center shrink-0 overflow-hidden">
                                    @if ($card['photo'])<img src="{{ $card['photo'] }}" alt="{{ $card['name'] }}" loading="lazy" class="w-full h-full object-cover">@else<x-theme::icon name="user" class="w-6 h-6 text-gray-400" />@endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h2 class="font-semibold text-gray-900 truncate">{{ $card['name'] }}</h2>
                                    @if ($card['title'])<p class="text-sm text-gray-600 truncate">{{ $card['title'] }}</p>@endif
                                </div>
                                @if ($card['country'])<span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded shrink-0">{{ $card['flag'] }} {{ $card['country'] }}</span>@endif
                            </div>
                            <button type="button" class="mt-4 text-gray-900 font-medium text-sm flex items-center gap-1 group-hover:gap-2 transition-all cursor-pointer self-start">
                                {{ __('international::international.page.read_more') }} <x-theme::icon name="chevron-right" class="w-4 h-4" />
                            </button>
                        </article>

                        <div x-show="open === @js($card['id'])" x-cloak x-transition.opacity class="fixed inset-0 z-[1000] bg-black/60 flex items-start sm:items-center justify-center p-4 overflow-y-auto"
                             role="dialog" aria-modal="true" aria-labelledby="{{ $card['id'] }}-name" @click.self="open = null; history.replaceState(null, '', location.pathname + location.search)">
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden w-full max-w-4xl my-8 relative">
                                <button type="button" @click="open = null; history.replaceState(null, '', location.pathname + location.search)" class="absolute top-4 right-4 p-2 rounded-full hover:bg-gray-100 cursor-pointer" aria-label="{{ __('international::international.page.close') }}"><x-theme::icon name="close" class="w-5 h-5 text-gray-600" /></button>
                                <div class="px-6 py-8 sm:px-8 sm:py-10 border-b border-gray-200 flex flex-col sm:flex-row items-start sm:items-center gap-4">
                                    <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center shrink-0 overflow-hidden">
                                        @if ($card['photo'])<img src="{{ $card['photo'] }}" alt="{{ $card['name'] }}" class="w-full h-full object-cover">@else<x-theme::icon name="user" class="w-10 h-10 text-gray-400" />@endif
                                    </div>
                                    <div class="flex-1">
                                        <h2 id="{{ $card['id'] }}-name" class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">{{ $card['name'] }}</h2>
                                        @if ($card['title'])<p class="text-base text-gray-600 mb-1">{{ $card['title'] }}</p>@endif
                                        <p class="text-sm text-gray-500">@if ($card['country']){{ $card['flag'] }} {{ $card['country'] }}@endif @if ($card['date']) · {{ __('international::international.page.visited', ['date' => $card['date']]) }}@endif</p>
                                    </div>
                                </div>
                                <div class="px-6 py-8 sm:px-8 sm:py-10 text-gray-700 leading-relaxed text-justify">
                                    @if ($card['body'])@include('theme::partials.page.body', ['body' => $card['body']])@endif
                                    @if ($card['album'])<p class="mt-6"><a href="{{ $card['album'] }}" class="text-primary font-semibold">{{ __('international::international.page.album') }} →</a></p>@endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                {{ $visitors->links('theme::partials.diu.pagination') }}
            @endif
        </div>
    </div>
</x-theme::layout>
