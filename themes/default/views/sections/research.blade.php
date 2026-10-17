{{--
    DIU Research: title, intro and link; figures counting up; up to three impact stories;
    partner logos sliding (grey) and the button under them.
    Contract: $section (title, subheading, content: heading, subheading, link_label, link_url, stats[number, suffix, label],
    stories[image, title, url], partners_title, partners[logo, name], partners_button_label, partners_button_url)
--}}
<x-theme::reveal>
    <section class="px-4 md:px-6 py-6 md:py-16 lg:px-12 bg-white text-slate-900">
        <div class="container mx-auto px-0">
            <div class="mb-8">
                <x-theme::heading tag="h2" color="primary">{{ $section->title ?: $section->content['heading'] }}</x-theme::heading>
                <p class="text-neutral-900 md:text-[24px] mb-4 max-w-6xl">{{ $section->subheading ?: $section->content['subheading'] }}</p>
                @if (filled($section->content['link_url']))
                    <x-theme::link-button :href="$section->content['link_url']" target="_blank" variant="primary">{{ $section->content['link_label'] }}</x-theme::link-button>
                @endif
            </div>

            @if (count($section->content['stats']))
                <div class="grid rounded-2xl md:bg-surface grid-cols-2 lg:grid-cols-4 gap-2 md:gap-8 mb-12">
                    @foreach ($section->content['stats'] as $stat)
                        <div @class(['text-center max-sm:rounded-2xl py-10 bg-surface border-gray-200', 'md:border-r' => ! $loop->last])>
                            <div class="text-3xl md:text-4xl lg:text-5xl font-bold text-primary mb-2"><x-theme::counter :value="$stat['number'] ?? 0" :suffix="$stat['suffix'] ?? ''" /></div>
                            <div class="text-base md:text-[24px] text-gray-600 leading-tight">{{ $stat['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if (count($section->content['stories']))
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                    @foreach (array_slice($section->content['stories'], 0, 3) as $story)
                        <x-theme::reveal :delay="$loop->index * 0.2" class="relative overflow-hidden rounded-lg aspect-[4/3] cursor-pointer group">
                            @if ($story['image'])
                                <img src="{{ $story['image']['url'] }}" alt="{{ $story['image']['alt'] ?: $story['title'] }}" loading="lazy" class="w-full h-full object-cover rounded-lg transform transition-transform duration-500 group-hover:scale-105">
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-black/100 via-black/30 to-transparent opacity-70 group-hover:opacity-90 transition-opacity duration-500"></div>
                            <div class="absolute bottom-0 p-4">
                                <a href="{{ $story['url'] ?: '#' }}" target="_blank" rel="noopener" class="text-base md:text-[22px] text-white font-semibold leading-tight hover:no-underline"><h3>{{ $story['title'] }}</h3></a>
                            </div>
                        </x-theme::reveal>
                    @endforeach
                </div>
            @endif

            @if (count($section->content['partners']))
                <div class="text-center my-10">
                    <x-theme::heading tag="h3" size="xs" color="primary" align="center" class="mb-12">{{ $section->content['partners_title'] }}</x-theme::heading>
                    <div class="mb-8 overflow-hidden" x-data="carousel({ perView: { 0: 2, 640: 3, 1024: 5 }, gap: 24, loop: true, autoplay: 2500 })" @mouseenter="stop()" @mouseleave="start()">
                        <div x-ref="viewport" class="overflow-hidden">
                            <div x-ref="track" class="flex" :style="trackStyle">
                                @foreach ($section->content['partners'] as $partner)
                                    <div class="shrink-0" :style="slideStyle">
                                        @if ($partner['logo'])
                                            <img src="{{ $partner['logo']['url'] }}" alt="{{ $partner['name'] }}" loading="lazy" class="h-20 md:h-[100px] w-full object-contain grayscale opacity-60 mx-auto">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @if (filled($section->content['partners_button_url']))
                        <div class="flex flex-wrap items-center justify-center gap-4 mt-5">
                            <x-theme::button :href="$section->content['partners_button_url']" target="_blank" variant="outlinePrimary" padding="px-4 py-2" size="text-lg" :right-icon="true" class="max-sm:w-full">{{ $section->content['partners_button_label'] }}</x-theme::button>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </section>
</x-theme::reveal>
