{{--
    DIU Facilities on the program page: picture cards in a slider (1, 2 from 540px, 3 from 1024px;
    autoplay 4 s) — title, two lines of text, round arrow; prev / next buttons.
    Contract: $entity, $items (in order)
--}}
<div class="relative" x-data="carousel({ perView: { 0: 1, 540: 2, 1024: 3 }, gap: 24, autoplay: 4000 })"
     @mouseenter="stop()" @mouseleave="start(); up()" @pointerdown="down($event)" @pointermove="move($event)" @pointerup="up()">
    <div x-ref="viewport" class="overflow-hidden pb-12">
        <div x-ref="track" class="flex" :style="trackStyle">
            @foreach ($items as $facility)
                <div class="shrink-0" :style="slideStyle">
                    <a href="{{ $facility->href() ?? '#' }}" @if ($facility->open_in_new_tab) target="_blank" rel="noopener" @endif draggable="false"
                       class="group relative isolate flex aspect-[16/9] sm:aspect-[4/5] md:aspect-[3/4] w-full overflow-hidden rounded-[20px] bg-black shadow-xl ring-1 ring-black/30 transition-transform hover:-translate-y-0.5 hover:no-underline focus:outline-none">
                        <img src="{{ $facility->firstMedia('image')?->url('medium') ?? $facility->firstMedia('image')?->url() ?? theme_asset('images/program/facility.webp') }}" alt="{{ $facility->title }}" loading="lazy" draggable="false"
                             class="absolute inset-0 h-full w-full object-cover object-center transition-transform duration-500 group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/25 to-black/0"></div>
                        <div class="relative mt-auto z-10 flex w-full items-end justify-between p-5 md:p-5">
                            <div class="max-w-[80%] text-white">
                                <h3 class="text-[16px] md:text-[20px] font-semibold leading-tight tracking-[-0.01em]">{{ $facility->title }}</h3>
                                <p class="mt-2 text-[13.5px] md:text-[15px] text-white/90 leading-snug line-clamp-2">{{ trim(html_entity_decode(strip_tags((string) ($facility->summary ?: $facility->body)))) }}</p>
                            </div>
                            <span class="ml-3 grid size-10 place-items-center shrink-0 rounded-full text-white backdrop-blur-sm ring-1 ring-white/30 transition-colors group-hover:bg-white group-hover:text-zinc-900 md:size-10">
                                <x-theme::icon name="chevron-right" class="h-4 w-4" />
                            </span>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
    <button type="button" @click="prev()" x-show="maxIndex > 0" class="absolute left-2 top-1/2 -translate-y-1/2 z-10 grid w-10 h-10 place-items-center rounded-full bg-white/90 backdrop-blur-sm shadow-lg text-gray-800 hover:bg-white transition-all duration-200 cursor-pointer disabled:opacity-40" :disabled="atStart" aria-label="{{ __('theme::theme.previous') }}">
        <x-theme::icon name="chevron-left" class="w-4 h-4" />
    </button>
    <button type="button" @click="next()" x-show="maxIndex > 0" class="absolute right-2 top-1/2 -translate-y-1/2 z-10 grid w-10 h-10 place-items-center rounded-full bg-white/90 backdrop-blur-sm shadow-lg text-gray-800 hover:bg-white transition-all duration-200 cursor-pointer disabled:opacity-40" :disabled="atEnd" aria-label="{{ __('theme::theme.next') }}">
        <x-theme::icon name="chevron-right" class="w-4 h-4" />
    </button>
</div>
