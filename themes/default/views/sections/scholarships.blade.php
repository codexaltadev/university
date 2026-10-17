{{--
    DIU Scholarships: navy band — title and link, intro, YouTube video (thumbnail, plays on click)
    with a quote, the big figure, text and the calculator button.
    Contract: $section (title, subheading, content: heading, subheading, link_label, link_url, youtube_id, quote,
    quote_author, stat, stat_title, stat_text, button_label, button_url)
--}}
<section class="px-4 py-20 lg:px-12 bg-secondary text-surface">
    <div class="container mx-auto px-0">
        <x-theme::reveal>
            <div class="flex flex-col lg:flex-row items-start justify-between mb-4">
                <x-theme::heading tag="h2" color="accent">{{ $section->title ?: $section->content['heading'] }}</x-theme::heading>
                @if (filled($section->content['link_url']))
                    <x-theme::link-button :href="$section->content['link_url']" class="max-sm:hidden">{{ $section->content['link_label'] }}</x-theme::link-button>
                @endif
            </div>
            <p class="text-base lg:text-[24px] text-gray-300 mb-0 md:mb-16 max-w-2xl">{{ $section->subheading ?: $section->content['subheading'] }}</p>
        </x-theme::reveal>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 md:gap-16 items-start">
            <x-theme::reveal direction="left" :delay="0.4">
                <div class="relative">
                    @if (filled($section->content['youtube_id']))
                        <div class="aspect-video bg-gray-200 rounded-lg overflow-hidden relative" x-data="{ playing: false }">
                            <template x-if="! playing">
                                <div class="absolute inset-0">
                                    <div class="absolute inset-0 bg-gradient-to-br from-gray-100 to-gray-300"></div>
                                    <img src="https://img.youtube.com/vi/{{ rawurlencode($section->content['youtube_id']) }}/maxresdefault.jpg" alt="" loading="lazy" class="absolute inset-0 w-full h-full object-cover">
                                    <button type="button" @click="playing = true" class="absolute inset-0 z-10 flex items-center justify-center cursor-pointer">
                                        <span class="w-16 h-16 bg-white/90 rounded-full flex items-center justify-center hover:bg-white transition-all group">
                                            <x-theme::icon name="play" class="w-6 h-6 text-black ml-1 group-hover:scale-110 transition-transform" />
                                        </span>
                                        <span class="sr-only">{{ __('theme::theme.play_video') }}</span>
                                    </button>
                                </div>
                            </template>
                            <template x-if="playing">
                                <iframe src="https://www.youtube.com/embed/{{ rawurlencode($section->content['youtube_id']) }}?autoplay=1" title="YouTube video player" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen class="absolute inset-0 w-full h-full border-0"></iframe>
                            </template>
                        </div>
                    @endif
                    @if (filled($section->content['quote']))
                        <div class="mt-6">
                            <p class="text-base text-gray-400 italic">{{ $section->content['quote'] }}</p>
                            <p class="text-sm text-gray-500">{{ $section->content['quote_author'] }}</p>
                        </div>
                    @endif
                </div>
            </x-theme::reveal>

            <x-theme::reveal direction="right" :delay="0.4">
                <div class="mb-0 md:mb-8">
                    <h3 class="text-3xl text-white lg:text-[42px] font-bold mb-4">{{ $section->content['stat'] }}</h3>
                    <p class="text-surface text-lg md:font-semibold mb-6">{{ $section->content['stat_title'] }}</p>
                    <p class="text-surface leading-relaxed mb-8">{{ $section->content['stat_text'] }}</p>
                </div>
                @if (filled($section->content['button_url']))
                    <x-theme::button :href="$section->content['button_url']" variant="outlinePrimary" padding="max-sm:px-2 max-sm:py-2 px-4 py-[11px]" :right-icon="true" class="!border-white hover:!border-white text-surface">
                        <span class="text-lg">{{ $section->content['button_label'] }}</span>
                    </x-theme::button>
                @endif
                @if (filled($section->content['link_url']))
                    <x-theme::button :href="$section->content['link_url']" variant="outlinePrimary" padding="max-sm:px-2 max-sm:py-2 px-4 py-[11px]" :right-icon="true" class="!border-white hover:!border-white text-surface mt-2 md:hidden">
                        <span class="text-lg">{{ $section->content['link_label'] }}</span>
                    </x-theme::button>
                @endif
            </x-theme::reveal>
        </div>
    </div>
</section>
