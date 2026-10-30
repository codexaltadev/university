{{--
    DIU Ambitions: blue band — big title, text, links; the microsite's pride alumni as cards, the
    chosen one wide (photo or video, name, designation, their words), the others narrow.
    Contract: $section (title, content: heading, text, links[label, url]), $items (Alumnus)
--}}
<div class="bg-primary px-4 py-26">
    <div class="container mx-auto px-0">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-12 items-center">
            <div class="col-span-1 md:col-span-5">
                <x-theme::heading tag="h2" size="md" color="white" class="mb-8">
                    @foreach (explode('|', (string) ($section->title ?: $section->content['heading'])) as $line){{ trim($line) }}@if (! $loop->last)<br>@endif @endforeach
                </x-theme::heading>
                <p class="text-surface mb-8 text-lg md:text-[22px] leading-relaxed">
                    @foreach (explode('|', (string) $section->content['text']) as $line){{ trim($line) }}@if (! $loop->last)<br>@endif @endforeach
                </p>
                <div class="space-y-4">
                    @foreach ($section->content['links'] as $link)
                        <a href="{{ $link['url'] ?: '#' }}" target="_blank" rel="noopener" class="text-2xl hover:text-gray-300 hover:no-underline font-bold border-b border-white flex w-fit items-center gap-2 text-white">
                            {{ $link['label'] }}<x-theme::icon name="arrow-up-right" class="w-5 h-5 [stroke-width:3]" />
                        </a>
                    @endforeach
                </div>
            </div>

            @if ($items && count($items))
                <div class="relative col-span-1 md:col-span-7" x-data="{ active: 0, playing: false }">
                    <div class="flex items-center justify-end gap-2 py-8">
                        @foreach (collect($items)->take(4) as $alumnus)
                            <div class="transition-all duration-500 ease-in-out cursor-pointer h-full" :class="active === {{ $loop->index }} ? 'w-7/8 opacity-100' : 'w-1/8 opacity-70 hover:opacity-90'"
                                 @click="if (active !== {{ $loop->index }}) { active = {{ $loop->index }}; playing = false }">
                                {{-- the wide card --}}
                                <div x-show="active === {{ $loop->index }}" class="h-[400px] border border-white backdrop-blur-sm rounded-4xl flex flex-col md:flex-row overflow-hidden" :class="playing ? 'bg-black' : 'bg-blue-800/90'">
                                    <div class="relative bg-black/30" :class="playing ? 'w-full' : 'w-full md:w-1/2'">
                                        @if ($embed = $alumnus->videoEmbed())
                                            <template x-if="playing"><iframe src="{{ $embed }}?autoplay=1" title="{{ $alumnus->name }}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen class="w-full h-[400px] border-0"></iframe></template>
                                        @endif
                                        @if ($photo = $alumnus->firstMedia('photo'))
                                            <img loading="lazy" decoding="async" x-show="! playing" src="{{ $photo->url('medium') ?? $photo->url() }}" alt="{{ $alumnus->name }}" class="w-full h-[400px] object-cover">
                                        @else
                                            <div x-show="! playing" class="w-full h-[400px] bg-blue-900"></div>
                                        @endif
                                        @if ($embed)
                                            <button type="button" x-show="! playing" @click.stop="playing = true" class="absolute inset-0 z-10 flex items-center justify-center bg-black/20 hover:bg-black/30 transition-colors cursor-pointer">
                                                <span class="w-16 h-16 rounded-full bg-white/90 hover:bg-white flex items-center justify-center transition-transform hover:scale-110"><x-theme::icon name="play" class="w-6 h-6 text-primary ml-1 fill-current" /></span>
                                                <span class="sr-only">{{ __('theme::theme.play_video') }}</span>
                                            </button>
                                            <button type="button" x-show="playing" x-cloak @click.stop="playing = false" class="absolute top-4 right-4 z-20 w-9 h-9 rounded-full bg-black/60 hover:bg-black/80 text-white flex items-center justify-center cursor-pointer">
                                                <x-theme::icon name="close" class="w-5 h-5" /><span class="sr-only">{{ __('theme::theme.close') }}</span>
                                            </button>
                                        @endif
                                        <div x-show="! playing">
                                            <div class="md:hidden block absolute inset-0 bg-black/60"></div>
                                            <div class="hidden md:block absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-black/80 to-transparent pointer-events-none"></div>
                                            <div class="absolute bottom-4 left-4 z-20 text-white">
                                                <p class="md:hidden mb-10 text-white leading-relaxed text-lg line-clamp-5">{{ \Illuminate\Support\Str::limit(strip_tags((string) $alumnus->story), 240) }}</p>
                                                <p class="font-semibold text-lg md:text-2xl">{{ $alumnus->name }}</p>
                                                <p class="text-blue-200 text-sm md:text-base">{{ $alumnus->headline() }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div x-show="! playing" class="hidden md:flex w-1/2 p-6 flex-col bg-[color-mix(in_srgb,var(--color-primary)_55%,white)] justify-center">
                                        <svg class="w-6 h-6 text-white mb-4 rotate-180" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2zM5 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z"/></svg>
                                        <p class="text-white leading-relaxed text-lg line-clamp-6">{{ \Illuminate\Support\Str::limit(strip_tags((string) $alumnus->story), 240) ?: $alumnus->headline() }}</p>
                                    </div>
                                </div>
                                {{-- the narrow card --}}
                                <div x-show="active !== {{ $loop->index }}" @if ($loop->first) x-cloak @endif class="relative rounded-4xl overflow-hidden">
                                    @if ($photo = $alumnus->firstMedia('photo'))
                                        <img decoding="async" src="{{ $photo->url('medium') ?? $photo->url() }}" alt="{{ $alumnus->name }}" loading="lazy" class="w-full h-[400px] object-cover hover:scale-105 transition-transform duration-300">
                                    @else
                                        <div class="w-full h-[400px] bg-blue-900"></div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
