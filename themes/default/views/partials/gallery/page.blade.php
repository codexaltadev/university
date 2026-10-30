{{--
    DIU gallery (DIUFronend app/(public)/gallery: layout, Sidebar, page): hero; "Browse by Category (NN)" —
    each category opens to its albums — beside the chosen category's or album's pictures (Media Library
    items, 20 a page, "Showing x - y of n photos") with a lightbox. Nothing chosen: the album cards.
    Contract: $page (GalleryPage), $base, $videos (link videos, album only), $uploads (uploaded videos, album only)
--}}
@include('theme::partials.diu.hero', [
    'title' => settings('gallery.title') ?: __('gallery::gallery.plural'),
    'subtitle' => e(settings('gallery.text')),
    'image' => media_url(settings('gallery.image'), 'large') ?? theme_image('page_hero_image', 'images/page-hero.webp'),
    'breadcrumbs' => array_values(array_filter([
        ['label' => __('theme::theme.home'), 'url' => url($base ?: '/')],
        ['label' => settings('gallery.title') ?: __('gallery::gallery.plural'), 'url' => $page->listingUrl()],
        $page->heading() ? ['label' => $page->heading(), 'url' => request()->url()] : null,
    ])),
])

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
    <div class="md:flex container mx-auto px-4 lg:px-8 py-8 gap-6">
        @if (($tree = $page->tree()) !== [])
            {{-- Desktop --}}
            <aside class="hidden lg:block w-full lg:w-[420px] shrink-0" aria-labelledby="gallery-browse">
                <div class="sticky top-32 space-y-6">
                    <h2 id="gallery-browse" class="text-xl font-bold border-b p-2 pb-2 border-gray-100 text-neutral-600">{{ __('gallery::gallery.page.browse') }} ({{ str_pad((string) count($tree), 2, '0', STR_PAD_LEFT) }})</h2>
                    <ul class="mt-4 border-l border-gray-400" role="list">
                        @foreach ($tree as $node)
                            <li x-data="{ open: @js($node['open']) }">
                                <div @class(['group flex w-full text-[22px] font-medium items-center justify-between my-2 border-l-3 p-[10px]', 'border-secondary bg-blue-50 text-slate-900' => $node['current'], 'border-transparent text-slate-700 hover:bg-blue-50' => ! $node['current']])>
                                    @if ($node['url'])
                                        <a href="{{ $node['url'] }}" class="truncate text-inherit hover:no-underline" @if ($node['current']) aria-current="page" @endif>{{ $node['name'] }}</a>
                                    @else
                                        <span class="truncate">{{ $node['name'] }}</span>
                                    @endif
                                    @if ($node['albums'] !== [])
                                        <button type="button" @click="open = ! open" :aria-expanded="open" class="p-1 cursor-pointer" aria-label="{{ $node['name'] }}">
                                            <x-theme::icon name="chevron-down" class="w-4 h-4 text-gray-600 transition-transform" ::class="open && 'rotate-180'" />
                                        </button>
                                    @endif
                                </div>
                                @if ($node['albums'] !== [])
                                    <ul class="ml-6 border-l border-gray-300" x-show="open" x-cloak>
                                        @foreach ($node['albums'] as $item)
                                            <li>
                                                <a href="{{ $item['url'] }}" @if ($item['current']) aria-current="page" @endif
                                                   @class(['flex w-full text-[18px] items-center my-2 border-l-2 p-[8px] hover:no-underline', 'border-secondary bg-blue-50 text-slate-900' => $item['current'], 'border-transparent text-slate-600 hover:bg-blue-50' => ! $item['current']])>
                                                    <span class="truncate">{{ $item['title'] }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </aside>

            {{-- Phones --}}
            <div class="lg:hidden mb-5 border-b border-neutral-200 pb-5 w-full md:w-auto" x-data="{ more: false }">
                <h2 class="text-xl sm:text-2xl font-semibold text-gray-600 pb-2 border-b border-gray-300 mb-4">{{ __('gallery::gallery.page.browse') }}</h2>
                <div class="bg-neutral-50 py-4 px-3">
                    <div class="flex bg-sky-50 items-center mb-4">
                        <div class="w-1 h-8 bg-secondary mr-4 mt-1 shrink-0"></div>
                        <p class="text-xl font-semibold text-primary leading-tight">{{ $page->heading() ?? __('gallery::gallery.page.browse') }}</p>
                    </div>
                    <div class="space-y-2 ml-5">
                        @foreach ($tree as $node)
                            <details @if ($node['open']) open @endif @if ($loop->index >= 3) x-show="more" x-cloak @endif class="border-b border-neutral-200 py-2">
                                <summary class="text-lg font-medium text-neutral-800 cursor-pointer">{{ $node['name'] }}</summary>
                                <div class="mt-2 space-y-1 pl-3">
                                    @if ($node['url'])<a href="{{ $node['url'] }}" class="block text-base text-primary py-1">{{ __('gallery::gallery.page.all_albums') }}</a>@endif
                                    @foreach ($node['albums'] as $item)
                                        <a href="{{ $item['url'] }}" @class(['block text-base py-1', 'font-semibold text-secondary' => $item['current'], 'text-slate-600' => ! $item['current']])>{{ $item['title'] }}</a>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach
                    </div>
                </div>
                @if (count($tree) > 3)
                    <button type="button" @click="more = ! more" class="flex items-center mx-auto mt-3 text-gray-700 cursor-pointer">
                        <span class="mr-2 border-b-2 border-gray-700 font-medium" x-text="more ? @js(__('theme::theme.program.see_less')) : @js(__('theme::theme.program.see_more'))">{{ __('theme::theme.program.see_more') }}</span>
                        <x-theme::icon name="chevron-down" class="w-4 h-4 transition-transform" ::class="more && 'rotate-180'" />
                    </button>
                @endif
            </div>
        @endif

        <div class="flex-1 min-w-0 p-0 lg:p-6">
            @if ($photos = $page->photos())
                <div class="mb-8">
                    <h1 class="text-4xl font-bold text-secondary mb-2">{{ $page->heading() }}</h1>
                    @if ($meta = $page->meta())<p class="text-sm font-medium text-primary mb-1">{{ $meta }}</p>@endif
                    @if ($showing = $page->showing())<p class="text-gray-600">{{ $showing }}</p>@endif
                </div>
                @if ($photos->isEmpty())
                    <p class="text-center py-20 text-gray-600">{{ __('gallery::gallery.page.none') }}</p>
                @else
                    <div x-data="{ open: null, slides: @js($photos->items()), x: 0,
                                   show(i) { this.open = (i + this.slides.length) % this.slides.length },
                                   key(e) { if (this.open === null) return; if (e.key === 'Escape') this.open = null; if (e.key === 'ArrowRight') this.show(this.open + 1); if (e.key === 'ArrowLeft') this.show(this.open - 1) } }"
                         @keydown.window="key($event)">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            @foreach ($photos as $i => $photo)
                                <a href="{{ $photo['src'] }}" @click.prevent="show({{ $loop->index }})" class="group relative aspect-square bg-white rounded-xl overflow-hidden shadow-md hover:shadow-2xl transition-all duration-300 block">
                                    <img src="{{ $photo['thumb'] }}" alt="{{ $photo['caption'] ?: $photo['album'] }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                    <span class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                        <span class="absolute bottom-0 left-0 right-0 p-4">
                                            @if ($photo['caption'])<span class="block text-white font-medium text-sm line-clamp-2">{{ $photo['caption'] }}</span>@endif
                                            <span class="block text-white/80 text-xs mt-1">{{ $photo['album'] }}</span>
                                        </span>
                                    </span>
                                </a>
                            @endforeach
                        </div>

                        <template x-if="open !== null">
                            <div class="fixed inset-0 bg-black/95 z-[1000] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="{{ __('gallery::gallery.lightbox') }}"
                                 @click.self="open = null" @touchstart="x = $event.touches[0].clientX" @touchend="let d = $event.changedTouches[0].clientX - x; if (Math.abs(d) > 40) show(open + (d < 0 ? 1 : -1))">
                                <button type="button" @click="open = null" class="absolute top-4 right-4 p-3 bg-white/10 hover:bg-white/20 rounded-full cursor-pointer" aria-label="{{ __('gallery::gallery.close') }}"><x-theme::icon name="close" class="w-6 h-6 text-white" /></button>
                                <button type="button" @click="show(open - 1)" class="absolute left-2 sm:left-6 top-1/2 -translate-y-1/2 p-3 bg-white/10 hover:bg-white/20 rounded-full cursor-pointer" aria-label="{{ __('gallery::gallery.previous') }}"><x-theme::icon name="chevron-left" class="w-6 h-6 text-white" /></button>
                                <button type="button" @click="show(open + 1)" class="absolute right-2 sm:right-6 top-1/2 -translate-y-1/2 p-3 bg-white/10 hover:bg-white/20 rounded-full cursor-pointer" aria-label="{{ __('gallery::gallery.next') }}"><x-theme::icon name="chevron-right" class="w-6 h-6 text-white" /></button>
                                <div class="max-w-6xl w-full">
                                    <img :src="slides[open].src" :alt="slides[open].caption || slides[open].album" class="w-full h-auto max-h-[85vh] object-contain rounded-lg">
                                    <div class="mt-4 text-center">
                                        <h3 class="text-white text-xl font-semibold" x-show="slides[open].caption" x-text="slides[open].caption"></h3>
                                        <p class="text-white/70 text-sm mt-1"><span x-text="slides[open].album"></span> · <span x-text="(open + 1) + ' / ' + slides.length"></span></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    {{ $photos->onEachSide(1)->links('theme::partials.diu.pagination') }}
                @endif
            @elseif (($cards = $page->cards()) !== [])
                <div class="mb-8"><h1 class="text-4xl font-bold text-secondary mb-2">{{ __('gallery::gallery.page.albums') }}</h1></div>
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                    @foreach ($cards as $card)
                        <a href="{{ $card['url'] }}" class="group bg-white rounded-xl overflow-hidden shadow-md hover:shadow-2xl transition-all duration-300 hover:no-underline">
                            <div class="aspect-[4/3] bg-gray-100 overflow-hidden">
                                @if ($card['cover'])<img src="{{ $card['cover'] }}" alt="" loading="lazy" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">@else<div class="w-full h-full flex items-center justify-center"><x-theme::icon name="image" class="w-12 h-12 text-gray-300" /></div>@endif
                            </div>
                            <div class="p-4">
                                <h2 class="font-semibold text-gray-900 line-clamp-2">{{ $card['title'] }}</h2>
                                <p class="text-sm text-gray-500 mt-1">{{ __('gallery::gallery.page.photos', ['count' => $card['photos']]) }}@if ($card['date']) · {{ $card['date'] }}@endif</p>
                            </div>
                        </a>
                    @endforeach
                </div>
                {{ $albums->links('theme::partials.diu.pagination') }}
            @else
                <div class="text-center py-20">
                    <div class="w-20 h-20 bg-secondary/10 rounded-full flex items-center justify-center mx-auto mb-6"><x-theme::icon name="image" class="w-10 h-10 text-secondary" /></div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">{{ __('gallery::gallery.page.select') }}</h3>
                    <p class="text-gray-600">{{ __('gallery::gallery.page.select_text') }}</p>
                </div>
            @endif

            @if (($uploads ?? collect())->isNotEmpty() || ($videos ?? collect())->isNotEmpty())
                <section class="mt-12" aria-labelledby="gallery-videos">
                    <h2 id="gallery-videos" class="text-2xl font-bold text-secondary mb-6">{{ __('gallery::gallery.page.videos') }}</h2>
                    <div class="grid sm:grid-cols-2 gap-6">
                        @foreach ($uploads ?? [] as $upload)
                            <figure class="bg-white rounded-xl overflow-hidden shadow-md">
                                <video src="{{ $upload['item']->url() }}" controls preload="metadata" class="w-full aspect-video bg-black"></video>
                                @if ($upload['caption'])<figcaption class="p-4 font-medium text-gray-800">{{ $upload['caption'] }}</figcaption>@endif
                            </figure>
                        @endforeach
                        @foreach ($videos ?? [] as $video)
                            <div class="bg-white rounded-xl overflow-hidden shadow-md p-4">@include('gallery::partials.video', ['video' => $video])</div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</div>
