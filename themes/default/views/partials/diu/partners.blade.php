{{-- DIU Parteners: title, grey logos sliding, the button under them. Contract: $content (partners_title, partners[logo, name], partners_button_label, partners_button_url) --}}
@if (count($content['partners']))
    <div class="text-center my-10">
        <x-theme::heading tag="h3" size="xs" color="primary" align="center" class="mb-12">{{ $content['partners_title'] }}</x-theme::heading>
        <div class="mb-8 overflow-hidden" x-data="carousel({ perView: { 0: 2, 640: 3, 1024: 5 }, gap: 40, loop: true, autoplay: 2500 })" @mouseenter="stop()" @mouseleave="start()">
            <div x-ref="viewport" class="overflow-hidden">
                <div x-ref="track" class="flex" :style="trackStyle">
                    @foreach ($content['partners'] as $partner)
                        <div class="shrink-0" :style="slideStyle">
                            @if ($partner['logo'])
                                <img src="{{ $partner['logo']['url'] }}" alt="{{ $partner['name'] }}" loading="lazy" class="h-20 md:h-[100px] w-full object-contain grayscale opacity-60 mx-auto">
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @if (filled($content['partners_button_url']))
            <div class="flex flex-wrap items-center justify-center gap-4 mt-5">
                <x-theme::button :href="$content['partners_button_url']" target="_blank" variant="outlinePrimary" padding="px-4 py-2" size="text-lg" :right-icon="true" class="max-sm:w-full">{{ $content['partners_button_label'] }}</x-theme::button>
            </div>
        @endif
    </div>
@endif
