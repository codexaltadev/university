{{--
    DIU MissionSection: headline with the yellow highlight, rankings slider (Accreditation
    "Rankings & Recognition" items), vision / mission switch, rotating quotes.
    Contract: $section (content: headline, highlight_prefix, highlight, button_label, button_url,
    vision_title, vision_text, mission_title, mission_points[text], quotes[text]), $items (Ranking / Accreditation)
--}}
<div class="bg-secondary px-2 text-white">
    <div class="container mx-auto px-4 md:px-0 py-12">
        <x-theme::reveal>
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between pb-4 md:pb-20 gap-6">
                <div class="flex-1 space-y-4">
                    <h2 class="text-lg lg:text-[28px] font-semibold leading-tight">
                        {{ $section->content['headline'] }}
                        <span class="block">{{ $section->content['highlight_prefix'] }} <span class="text-yellow-400 font-semibold text-[28px] lg:text-[38px]">{{ $section->content['highlight'] }}</span>.</span>
                    </h2>
                    <div class="h-[2px] lg:h-[3px] opacity-65 bg-white w-40 sm:w-60 md:w-96 lg:w-[27rem]"></div>
                </div>
                @if (filled($section->content['button_url']))
                    <x-theme::button :href="$section->content['button_url']" target="_blank" class="max-sm:hidden">{{ $section->content['button_label'] }}</x-theme::button>
                @endif
            </div>
        </x-theme::reveal>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-12">
            {{-- Rankings slider: right on desktop, first on phones --}}
            <div class="md:col-span-7 order-1 md:order-2">
                <x-theme::reveal direction="right" :delay="0.4" class="md:justify-end flex">
                    @if ($items && count($items))
                        <div class="overflow-hidden w-full md:max-w-4xl" x-data="carousel({ perView: { 0: 1.5, 640: 2, 1024: 3, 1350: 4 }, gap: 20 })"
                             @pointerdown="down($event)" @pointermove="move($event)" @pointerup="up()" @pointerleave="up()">
                            <div x-ref="viewport" class="overflow-hidden px-4 sm:px-6 lg:px-0">
                                <div x-ref="track" class="flex" :style="trackStyle">
                                    @foreach ($items as $item)
                                        <a href="{{ $item->ranking_url ?? $item->website_url ?? '#' }}" target="_blank" rel="noopener noreferrer" class="shrink-0 block rounded-lg p-2 text-blue-900 h-full hover:no-underline" :style="slideStyle">
                                            <span class="block bg-white rounded-lg">
                                                @if ($logo = $item->firstMedia('badge') ?? $item->firstMedia('logo'))
                                                    <img class="object-cover rounded-md w-full" src="{{ $logo->url('medium') ?? $logo->url() }}" alt="{{ $item->title ?? $item->label() }}" loading="lazy" draggable="false">
                                                @endif
                                            </span>
                                            <span class="block text-left pt-2 text-surface text-base leading-relaxed">{{ $item->title ?? $item->label().' '.$item->year }}</span>
                                            <span class="block text-left pt-2 text-surface text-xs leading-relaxed">{{ $item->summary ?? $item->agency }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                            <div class="max-sm:hidden flex space-x-2 justify-end mt-4 px-4 sm:px-6 lg:px-0">
                                <button type="button" @click="prev()" :disabled="atStart" class="cursor-pointer rounded-full p-2 transition-all duration-300 bg-[color-mix(in_srgb,var(--color-secondary)_92%,white)] border border-[color-mix(in_srgb,var(--color-secondary)_92%,white)] disabled:opacity-50 disabled:cursor-not-allowed">
                                    <x-theme::icon name="chevron-left" class="w-5 h-5 text-white" /><span class="sr-only">{{ __('theme::theme.previous') }}</span>
                                </button>
                                <button type="button" @click="next()" :disabled="atEnd" class="cursor-pointer rounded-full p-2 transition-all duration-300 bg-[color-mix(in_srgb,var(--color-secondary)_92%,white)] border border-[color-mix(in_srgb,var(--color-secondary)_92%,white)] disabled:opacity-50 disabled:cursor-not-allowed">
                                    <x-theme::icon name="chevron-right" class="w-5 h-5 text-white" /><span class="sr-only">{{ __('theme::theme.next') }}</span>
                                </button>
                            </div>
                        </div>
                    @endif
                </x-theme::reveal>
            </div>

            {{-- Vision / mission: left on desktop, hidden on phones (as on DIU) --}}
            <div class="max-sm:hidden block md:col-span-5 flex-1 space-y-8 order-2 md:order-1" x-data="{ tab: 0 }">
                <x-theme::reveal direction="left" :delay="0.4">
                    <div class="space-y-6">
                        <h2 class="text-2xl lg:text-[28px] font-bold" x-text="tab === 0 ? @js($section->content['vision_title']) : @js($section->content['mission_title'])">{{ $section->content['vision_title'] }}</h2>
                        <div x-show="tab === 0"><p>{{ $section->content['vision_text'] }}</p></div>
                        <ul class="space-y-2" x-show="tab === 1" x-cloak>
                            @foreach ($section->content['mission_points'] as $point)
                                <li class="text-base text-surface flex"><span class="w-2 h-2 bg-surface rounded-full mt-3 mr-4 flex-shrink-0"></span>{{ $point['text'] }}</li>
                            @endforeach
                        </ul>
                        <div class="flex space-x-4 mt-8">
                            @foreach ([0, 1] as $i)
                                <button type="button" @click="tab = {{ $i }}" class="cursor-pointer w-12 h-1 rounded transition-all duration-300" :class="tab === {{ $i }} ? 'bg-white' : 'bg-gray-400 hover:bg-gray-300'">
                                    <span class="sr-only">{{ $i === 0 ? $section->content['vision_title'] : $section->content['mission_title'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </x-theme::reveal>
            </div>
        </div>

        @if (filled($section->content['button_url']))
            <x-theme::button :href="$section->content['button_url']" target="_blank" class="flex sm:!hidden !w-full justify-center mt-6 mb-8">{{ $section->content['button_label'] }}</x-theme::button>
        @endif

        {{-- Rotating quotes (4 s), dots to pick one --}}
        @if (count($section->content['quotes']))
            <x-theme::reveal>
                <div class="md:mt-26 md:mb-12 text-center" x-data="{ q: 0, n: {{ count($section->content['quotes']) }} }" x-init="setInterval(() => q = (q + 1) % n, 4000)">
                    <div class="relative overflow-hidden min-h-[4.5rem] lg:min-h-[6rem]">
                        @foreach ($section->content['quotes'] as $quote)
                            <div x-show="q === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif
                                 x-transition:enter="transition duration-[900ms] ease-out" x-transition:enter-start="opacity-0 translate-y-5 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                 class="max-w-4xl mx-auto flex items-start justify-center gap-2 md:gap-4">
                                <svg class="w-[26px] md:w-[38px] flex-shrink-0 text-surface" viewBox="0 0 38 40" fill="currentColor" aria-hidden="true"><path d="M37.2.37 36.5 6.57c-2.47-.13-4.2.43-5.2 1.7-1 1.2-1.6 2.83-1.8 4.9-.13 2-.17 4.17-.1 6.5h7.8v19.5H22.7v-24.5c0-5.27 1.37-9.2 4.1-11.8 2.73-2.6 6.2-3.43 10.4-2.5Zm-22.7 0L13.8 6.57c-2.47-.13-4.2.43-5.2 1.7-1 1.2-1.6 2.83-1.8 4.9-.13 2-.17 4.17-.1 6.5h7.8v19.5H0v-24.5c0-5.27 1.37-9.2 4.1-11.8C6.83.27 10.3-.56 14.5.37Z"/></svg>
                                <div class="w-fit text-base text-surface lg:text-[28px] [&_strong]:font-bold">{!! \Illuminate\Support\Str::inlineMarkdown(e($quote['text'] ?? '')) !!}</div>
                                <svg class="w-[26px] md:w-[38px] flex-shrink-0 text-surface rotate-180" viewBox="0 0 38 40" fill="currentColor" aria-hidden="true"><path d="M37.2.37 36.5 6.57c-2.47-.13-4.2.43-5.2 1.7-1 1.2-1.6 2.83-1.8 4.9-.13 2-.17 4.17-.1 6.5h7.8v19.5H22.7v-24.5c0-5.27 1.37-9.2 4.1-11.8 2.73-2.6 6.2-3.43 10.4-2.5Zm-22.7 0L13.8 6.57c-2.47-.13-4.2.43-5.2 1.7-1 1.2-1.6 2.83-1.8 4.9-.13 2-.17 4.17-.1 6.5h7.8v19.5H0v-24.5c0-5.27 1.37-9.2 4.1-11.8C6.83.27 10.3-.56 14.5.37Z"/></svg>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex justify-center space-x-2 mt-8">
                        @foreach ($section->content['quotes'] as $quote)
                            <button type="button" @click="q = {{ $loop->index }}" class="cursor-pointer w-3 h-3 rounded-full transition-all duration-500 hover:scale-125" :class="q === {{ $loop->index }} ? 'bg-white' : 'bg-gray-500 hover:bg-gray-400'"><span class="sr-only">{{ $loop->iteration }}</span></button>
                        @endforeach
                    </div>
                </div>
            </x-theme::reveal>
        @endif
    </div>
</div>
