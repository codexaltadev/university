{{--
    DIU Department Programs: "Explore Our Academic Programs" — a card per program of this place
    (short name, degree, title, summary, duration, apply link), then the quick links.
    Contract: $section (title, content: heading, apply_label, quick_links_title, quick_links[label, url]), $items (by level: [level, programs])
--}}
<div class="bg-white py-8 md:py-16">
    <div class="container mx-auto px-4">
        <div class="mb-6 md:mb-8">
            <x-theme::heading tag="h2">{{ $section->title ?: $section->content['heading'] }}</x-theme::heading>
        </div>
        @if (($programs = collect($items ?? [])->flatMap(fn ($group) => $group['programs'])) && $programs->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 mb-8 md:mb-12">
                @foreach ($programs as $program)
                    <div class="bg-white p-4 md:p-6 rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <x-theme::icon name="graduation-cap" class="w-5 h-5" />
                                <span class="text-base text-gray-600 font-medium">{{ $program->short_name }}</span>
                            </div>
                            @if (filled($program->degree_name ?: $program->subtitle))
                                <div class="bg-gray-300 px-3 py-1 rounded-lg"><span class="text-sm font-medium text-secondary">{{ $program->degree_name ?: $program->subtitle }}</span></div>
                            @endif
                        </div>
                        <a href="{{ url((string) $program->url()) }}" class="hover:underline">
                            <h3 class="text-xl md:text-[24px] font-bold text-gray-900 mb-4 leading-tight">{{ $program->name }}</h3>
                        </a>
                        <p class="text-base text-gray-600 mb-6 leading-relaxed line-clamp-3">{{ \Illuminate\Support\Str::limit(strip_tags((string) ($program->ai_short_summary ?: $program->summary)), 220) }}</p>
                        <div class="border-t border-gray-200 mb-4"></div>
                        <div class="flex items-center justify-between">
                            <div class="text-gray-600"><span class="text-sm">{{ __('theme::theme.duration') }}: {{ $program->durationText() ?? '—' }}</span></div>
                            <a href="{{ $program->application_url ?: theme_option('header_cta_url') }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 text-secondary font-medium text-base hover:text-sky-700 hover:no-underline transition-colors group">
                                {{ $section->content['apply_label'] }}
                                <x-theme::icon name="arrow-up-right" class="w-4 h-4 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if (count($section->content['quick_links']))
            <div class="rounded-xl">
                <div class="pb-2 border-b border-gray-300 flex items-center gap-2">
                    <x-theme::icon name="link" class="w-4 h-4" />
                    <h3 class="text-xl font-bold text-neutral-600">{{ $section->content['quick_links_title'] }}</h3>
                </div>
                <div class="flex w-full flex-wrap mt-4 gap-2">
                    @foreach ($section->content['quick_links'] as $link)
                        <x-theme::button :href="$link['url'] ?: '#'" :target="str_starts_with((string) $link['url'], 'http') ? '_blank' : null" variant="outlineSecondary" rounded="full" padding="px-4 py-[10px]" size="text-base md:text-lg" class="min-w-max">{{ $link['label'] }}</x-theme::button>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
