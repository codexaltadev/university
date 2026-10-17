{{--
    DIU Testimonial: title, intro and "View All"; a slider (1.3 per view from 768px, autoplay 6 s,
    loop) — photo, name, designation, rule, the words; the slides beside the current one blurred.
    Contract: $section (title, subheading, content: heading, subheading, button_label, button_url), $items (Testimonial)
--}}
<x-theme::reveal>
    <section class="px-4 md:px-6 py-0 lg:px-12 md:py-10 bg-white text-slate-900">
        <div class="container mx-auto px-0">
            <div class="flex flex-col lg:flex-row items-start justify-between gap-4 pt-6 sm:pt-10">
                <div class="md:max-w-3/5">
                    <x-theme::heading tag="h2" color="primary">{{ $section->title ?: $section->content['heading'] }}</x-theme::heading>
                    <p class="mt-3 text-neutral-500 text-base sm:text-[24px]">{{ $section->subheading ?: $section->content['subheading'] }}</p>
                </div>
                @if (filled($section->content['button_url']))
                    <x-theme::button :href="$section->content['button_url']" target="_blank" variant="outlinePrimary" :right-icon="true">{{ $section->content['button_label'] }}</x-theme::button>
                @endif
            </div>

            @if ($items && count($items))
                <div class="mt-10 cursor-grab select-none" x-data="carousel({ perView: { 0: 1, 768: 1.3 }, gap: 24, loop: true, autoplay: 6000 })"
                     @mouseenter="stop()" @mouseleave="start(); up()" @pointerdown="down($event)" @pointermove="move($event)" @pointerup="up()">
                    <div x-ref="viewport" class="overflow-hidden pb-12">
                        <div x-ref="track" class="flex" :style="trackStyle">
                            @foreach ($items as $testimonial)
                                <div class="shrink-0 bg-white" :style="slideStyle">
                                    <div class="grid grid-cols-1 md:grid-cols-12 gap-0 md:gap-8 items-start">
                                        <div class="md:col-span-4">
                                            <div class="relative h-80 w-full overflow-hidden rounded-lg border border-neutral-200 transition-all duration-500" :class="isActive({{ $loop->index }}) ? 'blur-none opacity-100' : 'blur-[3px] opacity-80'">
                                                @if ($photo = $testimonial->person()?->photoUrl() ?? $testimonial->firstMedia('photo')?->url('medium'))
                                                    <img src="{{ $photo }}" alt="{{ $testimonial->person()?->name() }}" loading="lazy" draggable="false" class="object-cover w-full h-full">
                                                @else
                                                    <div class="h-full w-full bg-neutral-200 rounded-lg"></div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="md:col-span-7">
                                            <div class="px-0 sm:px-6 py-8 md:py-12 h-80 flex flex-col justify-center">
                                                <a href="{{ url('/'.trim($section->content['button_url'] ?: 'testimonials', '/')) }}" class="hover:underline hover:text-blue-500">
                                                    <h3 class="text-xl sm:text-2xl font-semibold">{{ $testimonial->person()?->name() }}</h3>
                                                </a>
                                                <p class="mt-2 text-neutral-500 text-sm sm:text-lg">{{ $testimonial->person()?->designation() }}</p>
                                                <hr class="my-6 border-neutral-200">
                                                <div class="text-base md:text-xl leading-8 text-neutral-800 overflow-hidden line-clamp-5">{{ \Illuminate\Support\Str::limit(strip_tags((string) $testimonial->quote), 300) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex justify-center gap-2 -mt-6">
                        @foreach ($items as $testimonial)
                            <button type="button" @click="go({{ $loop->index }})" class="w-2 h-2 rounded-full transition-all cursor-pointer" :class="isActive({{ $loop->index }}) ? 'bg-blue-600' : 'bg-black/20'"><span class="sr-only">{{ $loop->iteration }}</span></button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
</x-theme::reveal>
