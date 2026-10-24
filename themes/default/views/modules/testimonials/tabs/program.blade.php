{{--
    DIU Testimonials on the program page: quote cards in a slider (1, 1.5 from 768px, 2 from 1024px;
    loop, autoplay 6 s) — big quote mark, the words, round photo, name, designation.
    Contract: $entity, $items (in order)
--}}
<div class="relative pb-10 cursor-grab select-none" x-data="carousel({ perView: { 0: 1, 768: 1.5, 1024: 2 }, gap: 32, loop: true, autoplay: 6000 })"
     @mouseenter="stop()" @mouseleave="start(); up()" @pointerdown="down($event)" @pointermove="move($event)" @pointerup="up()">
    <div x-ref="viewport" class="overflow-hidden py-6">
        <div x-ref="track" class="flex items-stretch" :style="trackStyle">
            @foreach ($items as $testimonial)
                <div class="shrink-0 !h-auto" :style="slideStyle">
                    <div class="group relative h-full px-4">
                        <div class="relative h-full bg-white rounded-3xl p-10 md:p-12 shadow border border-gray-100 transition-all duration-700 hover:shadow-xl hover:-translate-y-4 hover:border-transparent overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-t from-secondary/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
                            <x-theme::icon name="quote" width="96" height="96" stroke-width="1" class="absolute top-8 left-8 w-24 h-24 text-secondary/10 -rotate-12" />
                            <div class="relative z-10">
                                <blockquote class="text-lg font-medium text-gray-700 italic leading-relaxed mb-12">"{{ trim(html_entity_decode(strip_tags((string) $testimonial->quote))) }}"</blockquote>
                                @if ($person = $testimonial->person())
                                    <div class="flex items-center gap-6">
                                        <div class="relative flex-shrink-0">
                                            <div class="w-20 h-20 md:w-24 md:h-24 rounded-full overflow-hidden ring-4 ring-white shadow transition-transform duration-500 group-hover:scale-110 bg-neutral-200">
                                                @if ($photo = $testimonial->photoUrl())
                                                    <img src="{{ $photo }}" alt="{{ $person->name() }}" loading="lazy" draggable="false" class="w-full h-full object-cover">
                                                @endif
                                            </div>
                                        </div>
                                        <div>
                                            <h4 class="text-xl font-bold text-gray-900">{{ $person->name() }}</h4>
                                            @if ($title = $person->assignment->role_label ?: $person->designation())
                                                <p class="text-secondary font-semibold text-sm tracking-wider mt-1">{{ $title }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
