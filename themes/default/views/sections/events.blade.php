{{--
    DIU RecentEvents: "Recent Events" title and button; an accordion of events — the open one
    shows its picture with the badge and date, the title over a dark fade.
    Contract: $section (content: title_strong, title_light, button_label, button_url, badge), $items (Event)
--}}
<x-theme::reveal>
    <section class="px-4 md:px-6 py-6 md:py-6 lg:px-12 bg-white text-slate-900">
        <div class="container mx-auto px-0">
            <div class="flex flex-col lg:flex-row items-start justify-between gap-4">
                <h2 class="text-[24px] md:text-[48px] font-extrabold tracking-tight leading-none">
                    <span class="opacity-90">{{ $section->content['title_strong'] }}</span>
                    <span class="opacity-60">{{ $section->content['title_light'] }}</span>
                </h2>
                @if (filled($section->content['button_url']))
                    <x-theme::button :href="$section->content['button_url']" variant="outlinePrimary" :right-icon="true" class="max-sm:w-full max-sm:mt-4">{{ $section->content['button_label'] }}</x-theme::button>
                @endif
            </div>

            @if ($items && count($items))
                <div class="mt-6 divide-y divide-gray-300 rounded-md overflow-hidden" x-data="{ open: 0 }">
                    @foreach ($items as $event)
                        <div class="border-b border-gray-200 last:border-b-0">
                            <button type="button" x-show="open !== {{ $loop->index }}" @click="open = {{ $loop->index }}" :aria-expanded="open === {{ $loop->index }}"
                                    class="group w-full text-left flex flex-col lg:flex-row lg:items-center justify-between gap-6 py-5 cursor-pointer hover:bg-gray-50 hover:translate-x-1 transition-all duration-200">
                                <span class="flex-1 flex items-center justify-between">
                                    <span>
                                        <span class="font-bold mb-2 border-b-2 inline-block text-lg sm:text-[24px] leading-relaxed">{{ $event->title }}</span>
                                        @if (filled($event->organizer_label ?: $event->venue))
                                            <span class="block text-gray-500 text-base leading-relaxed">{{ $event->organizer_label ?: $event->venue }}</span>
                                        @endif
                                    </span>
                                    <x-theme::icon name="chevron-down" class="ml-4 flex-shrink-0 w-6 h-6 text-secondary [stroke-width:3]" />
                                </span>
                            </button>
                            <div x-show="open === {{ $loop->index }}" x-collapse @if (! $loop->first) x-cloak @endif>
                                <div class="pb-6 pt-5">
                                    <div class="relative h-[260px] sm:h-[360px] md:h-[420px] lg:h-[460px] w-full rounded-lg overflow-hidden shadow-lg">
                                        @if ($image = $event->firstMedia('featured_image'))
                                            <img src="{{ $image->url('large') ?? $image->url() }}" alt="{{ $event->title }}" class="absolute inset-0 h-full w-full object-cover" @if (! $loop->first) loading="lazy" @endif>
                                        @else
                                            <div class="h-full w-full bg-neutral-700"></div>
                                        @endif
                                        <div class="absolute left-4 top-4 z-[2] flex items-center gap-2">
                                            <span class="inline-flex rounded-md bg-gray-400 text-white px-3 py-2 text-base font-semibold tracking-wide ring-1 ring-white/30 backdrop-blur">
                                                {{ $section->content['badge'] }}<span class="pl-2 font-medium">{{ $event->starts_at?->format('Y-m-d') }}</span>
                                            </span>
                                        </div>
                                        <div class="absolute inset-x-0 bottom-0 h-80 bg-gradient-to-t from-black/85 via-black/60 to-transparent"></div>
                                        <div class="absolute left-4 bottom-4 z-[2]">
                                            <a href="{{ url($event->url()) }}" class="hover:no-underline">
                                                <h3 class="text-white w-[75%] hover:underline text-[24px] md:text-[32px] font-bold line-clamp-2 leading-[1.1] drop-shadow">{{ $event->title }}</h3>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-theme::reveal>
