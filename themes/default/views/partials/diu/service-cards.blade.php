{{--
    DIU EssentialCampusServices: title, line, a slider of tall picture cards (1 / 2 / 3 / 4 per view, autoplay 4 s).
    Contract: $heading, $subheading, $cards (list of [image => ['url'] | null, title, text, url])
--}}
<section class="w-full bg-white">
    <div class="mx-auto container px-4 sm:px-6 lg:px-4 py-10 lg:py-18">
        <div class="mb-6 sm:mb-8">
            <x-theme::heading tag="h2" size="md">{{ $heading }}</x-theme::heading>
            <p class="mt-2 max-w-4xl text-base md:text-lg text-black/70">{{ $subheading }}</p>
        </div>
        <div class="relative" x-data="carousel({ perView: { 0: 1, 640: 2, 768: 3, 1024: 4 }, gap: 20, autoplay: 4000 })" @mouseenter="stop()" @mouseleave="start()">
            <div x-ref="viewport" class="overflow-hidden pb-12">
                <div x-ref="track" class="flex" :style="trackStyle">
                    @foreach ($cards as $card)
                        <div class="shrink-0" :style="slideStyle">
                            <a href="{{ $card['url'] ?: '#' }}" @if (str_starts_with((string) $card['url'], 'http')) target="_blank" rel="noopener" @endif
                               class="group relative isolate flex aspect-[16/9] sm:aspect-[4/5] md:aspect-[3/4] w-full overflow-hidden rounded-[20px] bg-black shadow-xl ring-1 ring-black/30 transition-transform hover:-translate-y-0.5 hover:no-underline">
                                @if ($card['image'])
                                    <img src="{{ $card['image']['url'] }}" alt="{{ $card['title'] }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover object-center">
                                @endif
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/25 to-black/0"></div>
                                <div class="relative mt-auto z-10 flex w-full items-end justify-between p-5">
                                    <div class="max-w-[80%] text-white">
                                        <h3 class="md:text-[20px] font-semibold leading-tight tracking-[-0.01em]">{{ $card['title'] }}</h3>
                                        <p class="mt-2 text-[13.5px] md:text-[15px] text-white/90 leading-snug">{{ $card['text'] }}</p>
                                    </div>
                                    <span class="ml-3 grid size-10 place-items-center shrink-0 rounded-full text-white backdrop-blur-sm ring-1 ring-white/30 transition-colors group-hover:bg-white group-hover:text-zinc-900">
                                        <x-theme::icon name="chevron-right" class="h-4 w-4" />
                                    </span>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
            <button type="button" x-show="maxIndex > 0" @click="prev()" class="absolute left-2 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/90 backdrop-blur-sm rounded-full shadow-lg text-gray-800 hover:bg-white flex items-center justify-center cursor-pointer z-10"><x-theme::icon name="chevron-left" class="w-4 h-4" /><span class="sr-only">{{ __('theme::theme.previous') }}</span></button>
            <button type="button" x-show="maxIndex > 0" @click="next()" class="absolute right-2 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/90 backdrop-blur-sm rounded-full shadow-lg text-gray-800 hover:bg-white flex items-center justify-center cursor-pointer z-10"><x-theme::icon name="chevron-right" class="w-4 h-4" /><span class="sr-only">{{ __('theme::theme.next') }}</span></button>
            <div x-show="maxIndex > 0" class="flex justify-center gap-2 -mt-6">
                <template x-for="i in maxIndex + 1" :key="i">
                    <button type="button" @click="go(i - 1)" class="w-3 h-3 rounded-full transition-all cursor-pointer" :class="index === i - 1 ? 'bg-primary scale-125' : 'bg-black/30'"><span class="sr-only" x-text="i"></span></button>
                </template>
            </div>
        </div>
    </div>
</section>
