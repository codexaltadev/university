{{--
    DIU program page (DIUFronend department/[slug]/program/[id] ProgramClient): full-screen hero with
    figures, key facts and the video; then "Browse by Section" on the left (sections, Quick Links,
    Downloadable Materials) beside the AI summary and the sections — Overview, Variations, Majors,
    Accreditations, Eligibilities, Memberships, Facilities, Facilitators, Future Career, Testimonials,
    Tuition Fees — and the other modules' tabs. Empty sections are left out, with their menu entry.
    Contract: $entity (Program), $context, $page (ProgramPage: the parts worked out)
--}}

{{-- Hero --}}
<section class="page-hero relative w-full" style="margin-top: calc(-1 * var(--header-h, 0px))" x-data="{ video: false }" @keydown.escape.window="video = false">
    <div class="relative w-full min-h-screen flex items-center overflow-hidden">
        <div class="absolute inset-0 bg-secondary">
            @if ($image = $page->image())
                <img src="{{ $image->url('large') ?? $image->url() }}" alt="{{ $entity->name }}" fetchpriority="high" decoding="async" class="w-full h-full object-cover">
            @endif
        </div>
        <div class="absolute inset-0 bg-black/60"></div>

        <div class="container mx-auto relative z-10 h-full justify-between flex flex-col lg:flex-row items-center px-6 lg:px-8 py-24 md:py-32">
            <div class="text-white w-full max-w-full text-center md:text-left">
                <h1 class="text-4xl md:text-6xl font-bold mb-4 md:mb-2 leading-tight">{{ $entity->name }}</h1>
                @if (filled($entity->subtitle))
                    <p class="text-base md:text-xl mb-8 md:mb-12 text-gray-200 max-w-xl mx-auto md:mx-0">{{ $entity->subtitle }}</p>
                @endif

                @if (($stats = $page->stats()) !== [])
                    <div @class(['grid gap-6 md:gap-12 lg:gap-20 mb-10 md:mb-8', count($stats) > 3 ? 'grid-cols-2 sm:grid-cols-4' : 'grid-cols-3'])>
                        @foreach ($stats as $stat)
                            <div class="group">
                                <div class="text-3xl md:text-5xl font-bold mb-2 text-white group-hover:text-blue-300 transition-colors"
                                     x-data="counter({{ (float) $stat['value'] }}, @js($stat['suffix']))" x-intersect.once="run()" x-text="text">{{ $stat['display'] }}</div>
                                <div class="text-xs md:text-base text-gray-300">{{ $stat['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if (($facts = $page->facts()) !== [])
                    <div class="max-w-3xl bg-white/10 backdrop-blur-md rounded-xl p-4 md:p-8 grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-8 text-[11px] xs:text-xs sm:text-sm text-left">
                        @foreach ($facts as $key => $value)
                            <div>
                                <div class="font-semibold mb-1">{{ __("theme::theme.program.facts.{$key}") }}</div>
                                <div class="text-gray-300">{{ __("theme::theme.program.fact_values.{$key}", ['value' => $value]) }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($youtube = $page->youtubeId())
                <div class="w-full md:w-1/2 flex mt-12 lg:mt-0 justify-center">
                    <button type="button" @click="video = true" class="group relative cursor-pointer" aria-label="{{ __('theme::theme.play_video') }}">
                        <div class="absolute inset-0 w-20 h-20 md:w-24 md:h-24 bg-white rounded-full animate-pulse opacity-20"></div>
                        <div class="relative w-20 h-20 md:w-24 md:h-24 bg-gradient-to-br from-white to-gray-100 rounded-full flex items-center justify-center group-hover:from-gray-100 group-hover:to-white transition-all duration-300 shadow-2xl group-hover:shadow-blue-500/50 group-hover:scale-110">
                            <x-theme::icon name="play" width="40" height="40" class="text-secondary fill-current ml-1 group-hover:scale-125 transition-transform duration-300" />
                        </div>
                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-3 text-white text-sm whitespace-nowrap bg-black/70 px-3 py-2 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none backdrop-blur-sm">{{ __('theme::theme.program.full_screen') }}</div>
                    </button>
                </div>
            @endif
        </div>
    </div>

    @if ($youtube)
        <template x-if="video">
            <div class="fixed inset-0 bg-black/95 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="{{ $entity->name }}">
                <div class="relative w-full max-w-6xl">
                    <button type="button" @click="video = false" class="absolute -top-14 right-5 text-white hover:text-gray-300 transition-colors bg-white/10 p-2 rounded-full backdrop-blur-sm cursor-pointer" aria-label="{{ __('theme::theme.close') }}">
                        <x-theme::icon name="close" width="28" height="28" />
                    </button>
                    <div class="relative w-full aspect-video rounded-xl shadow-lg border-4 border-white bg-black overflow-hidden">
                        <iframe src="https://www.youtube.com/embed/{{ $youtube }}?autoplay=1" title="{{ $entity->name }}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen class="absolute inset-0 w-full h-full border-0"></iframe>
                    </div>
                </div>
            </div>
        </template>
    @endif
</section>

<div class="min-h-screen bg-white" x-data="{ current: @js($page->sections()[0]['anchor'] ?? null), more: false }">
    <div class="container mx-auto px-4 sm:px-6 py-12">
        <div class="flex flex-col lg:flex-row gap-4 xl:gap-8 lg:gap-12">
            {{-- Browse by Section: desktop --}}
            <aside class="hidden lg:block w-full lg:w-[320px] xl:w-[400px] shrink-0" aria-labelledby="program-sections-title">
                <div class="sticky top-32 space-y-6">
                    <div class="rounded-xl">
                        <h2 id="program-sections-title" class="text-xl font-bold border-b p-2 pb-2 border-gray-100 text-neutral-600">{{ __('theme::theme.program.browse') }} ({{ str_pad((string) count($page->sections()), 2, '0', STR_PAD_LEFT) }})</h2>
                        <div class="max-h-[calc(100vh-200px)] overflow-y-auto pr-2 pb-6">
                            <ul class="mt-4 border-l border-gray-400" role="list">
                                @foreach ($page->sections() as $section)
                                    <li>
                                        <a href="#{{ $section['anchor'] }}" @click="current = @js($section['anchor'])" :aria-current="current === @js($section['anchor']) ? 'true' : null"
                                           class="group flex w-full text-[22px] text-neutral-800 font-medium items-center my-1 rounded-none p-[10px] text-left hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-600"
                                           :class="current === @js($section['anchor']) ? 'border-l-3 border-primary bg-blue-50 text-slate-900' : 'border-none text-slate-700 hover:bg-blue-50'">
                                            <span class="truncate">{{ $section['name'] ?? __("theme::theme.program.menu.{$section['key']}") }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                            @include('theme::partials.program.quick-links', ['links' => $page->quickLinks()])
                            @include('theme::partials.program.materials', ['materials' => $page->materials(), 'brochure' => $page->brochure()])
                        </div>
                    </div>
                </div>
            </aside>

            {{-- Browse by Section: mobile --}}
            @if ($page->sections() !== [])
                <div class="lg:hidden mb-5 border-b border-neutral-200 relative w-full" :class="more ? 'pb-16' : 'pb-5'">
                    <div class="mb-6 sm:mb-8">
                        <h2 class="text-xl sm:text-2xl font-semibold text-gray-600 pb-2 border-b border-gray-300">{{ __('theme::theme.program.browse') }}</h2>
                    </div>
                    <div class="bg-neutral-50 py-4 sm:p-6 mb-2 sm:mb-3 relative overflow-hidden">
                        <div class="flex bg-sky-50 items-center mb-4 sm:mb-6">
                            <div class="w-1 sm:w-1.5 h-8 bg-primary mr-4 sm:mr-6 mt-1 flex-shrink-0"></div>
                            <h3 class="text-xl sm:text-3xl font-semibold text-secondary leading-tight">
                                @foreach ($page->sections() as $section)<span x-show="current === @js($section['anchor'])" @if (! $loop->first) x-cloak @endif>{{ $section['name'] ?? __("theme::theme.program.menu.{$section['key']}") }}</span>@endforeach
                            </h3>
                        </div>
                        <div class="space-y-2 sm:space-y-3 ml-5 sm:ml-7 relative">
                            @foreach ($page->sections() as $section)
                                <a href="#{{ $section['anchor'] }}" @click="current = @js($section['anchor'])" @if ($loop->index >= 3) x-show="more" x-cloak @endif
                                   class="block text-lg sm:text-2xl font-medium transition-all duration-200 hover:no-underline"
                                   :class="(more ? 'border-b py-2 border-neutral-200 ' : '') + (more || {{ $loop->index }} === 0 ? 'text-neutral-800' : 'text-gray-400 pointer-events-none') + (! more && {{ $loop->index }} === 2 ? ' opacity-0' : '')">{{ $section['name'] ?? __("theme::theme.program.menu.{$section['key']}") }}</a>
                            @endforeach
                        </div>
                        <div x-show="! more" class="absolute bottom-0 left-0 right-0 h-16 bg-gradient-to-t from-white via-white/60 to-transparent pointer-events-none"></div>
                    </div>
                    <div x-show="more" x-cloak>
                        @include('theme::partials.program.quick-links', ['links' => $page->quickLinks()])
                        @include('theme::partials.program.materials', ['materials' => $page->materials(), 'brochure' => $page->brochure()])
                    </div>
                    <div class="text-center absolute mt-2 w-full" :class="more ? 'bottom-5' : 'bottom-9'">
                        <button type="button" @click="more = ! more" class="flex items-center justify-center mx-auto text-gray-700 hover:text-gray-900 transition-colors pb-1 cursor-pointer">
                            <span class="mr-2 border-b-2 border-gray-700 font-medium text-base sm:text-lg" x-text="more ? @js(__('theme::theme.program.see_less')) : @js(__('theme::theme.program.see_more'))">{{ __('theme::theme.program.see_more') }}</span>
                            <x-theme::icon name="chevron-down" class="w-4 h-4 sm:w-5 sm:h-5 transition-transform" ::class="more && 'rotate-180'" />
                        </button>
                    </div>
                </div>
            @endif

            <div class="flex-1 w-full min-w-0">
                {{-- AI Summary --}}
                @if ($summary = $page->aiSummary())
                                        <x-theme::reveal>
                        <div class="relative z-10 bg-gradient-to-b from-secondary via-secondary/80 to-sky-700 rounded-2xl border border-white/10 backdrop-blur-sm overflow-hidden transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_10px_25px_rgba(0,51,102,0.15)]" x-data="{ open: false }">
                            <div class="flex items-center p-6 pb-4">
                                <div class="flex items-center gap-3">
                                    <div class="relative p-3 bg-white/10 backdrop-blur-md rounded-xl border border-white/20">
                                        <x-theme::icon name="sparkles" width="24" height="24" class="w-6 h-6 text-white" />
                                    </div>
                                    <h2 class="text-xl md:text-4xl font-bold text-white">{{ __('theme::theme.program.ai_summary') }}</h2>
                                </div>
                            </div>
                            <div class="px-2 lg:px-6 pb-6 space-y-6">
                                <div class="text-white leading-relaxed text-base md:text-lg">
                                    @if ($short = $page->aiSummaryShort())
                                        <span x-show="! open">{{ $short }}</span>
                                        <span x-show="open" x-cloak>{{ $summary }}</span>
                                    @else
                                        {{ $summary }}
                                    @endif
                                </div>
                                @if ($page->aiSummaryShort())
                                    <button type="button" @click="open = ! open" class="w-full flex justify-center items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-md border border-white/20 rounded-full shadow-lg hover:bg-white/15 transition-colors cursor-pointer">
                                        <x-theme::icon name="chevron-up" class="w-4 h-4 text-white transition-transform duration-300" ::class="open ? 'rotate-0' : 'rotate-180'" />
                                        <span class="text-sm text-white md:text-base font-medium" x-text="open ? @js(__('theme::theme.program.see_less')) : @js(__('theme::theme.program.see_more'))">{{ __('theme::theme.program.see_more') }}</span>
                                    </button>
                                @endif
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-4">
                                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                                        <div class="flex items-center gap-2 px-3 py-1.5 bg-white/10 backdrop-blur-md border border-white/20 rounded-full shadow-lg">
                                            <span class="w-2 h-2 bg-green-400 rounded-full shadow-lg animate-pulse"></span>
                                            <span class="text-lg text-white font-medium">{{ __('theme::theme.program.generated_by_ai') }}</span>
                                        </div>
                                        <span class="text-[10px] lg:text-sm text-white/70">{{ __('theme::theme.program.ai_mistakes') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-theme::reveal>
                @endif

                {{-- Program Overview --}}
                @if ($overview = $page->overview())
                    <div id="overview" class="container mx-auto px-4 md:px-0 py-10 text-neutral-800 scroll-mt-28">
                        <x-theme::heading tag="h2" color="primary">{{ __('theme::theme.program.program_overview') }}</x-theme::heading>
                        @foreach ($overview as $key => $value)
                            <section class="py-6 scroll-mt-28">
                                <h3 class="text-2xl md:text-3xl font-semibold mb-4">{{ __("theme::theme.program.{$key}") }}</h3>
                                <div class="program-content w-full">{!! $value !!}</div>
                            </section>
                        @endforeach
                    </div>
                @endif

                {{-- Program Variations --}}
                @if (($variants = $page->variants())->isNotEmpty())
                    <div class="bg-white">
                        <section id="variants" class="py-10 scroll-mt-28">
                            <div class="container mx-auto px-4 md:px-0">
                                <div class="mb-6 md:mb-8">
                                    <x-theme::heading tag="h2" color="primary">{{ __('theme::theme.program.variations') }}</x-theme::heading>
                                    @if ($intro = $entity->intro('variants'))<p class="text-neutral-900 md:text-[24px] mb-4 max-w-5xl">{{ $intro }}</p>@endif
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8 md:mb-12">
                                    @foreach ($variants as $variant)
                                        <div class="bg-white p-4 rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300">
                                            <div class="flex items-start justify-between mb-8">
                                                <div class="flex items-center gap-3">
                                                    <x-theme::icon name="graduation-cap" class="w-5 h-5 shrink-0" />
                                                    <span class="text-base text-gray-600 font-medium">{{ $variant->title }}</span>
                                                </div>
                                            </div>
                                            <a href="{{ $variant->relatedProgram?->url() ? url((string) $variant->relatedProgram->url()) : ($variant->buttonHref() ?: '#') }}" class="hover:underline">
                                                <h3 class="text-xl md:text-[24px] font-bold text-gray-900 mb-4 leading-tight">{{ $variant->title }}</h3>
                                            </a>
                                            <p class="text-base text-gray-600 mb-6 leading-relaxed line-clamp-3">{{ $variant->short_description }}</p>
                                            <div class="border-t border-gray-200 mb-4"></div>
                                            <div class="flex items-center justify-between">
                                                <div class="text-gray-600"><span class="text-sm">{{ __('theme::theme.duration') }}: {{ $variant->relatedProgram?->durationText() ?? $entity->durationText() ?? '—' }}</span></div>
                                                @if (filled($variant->details) && $variant->buttonHref())   {{-- DIU: the button text only with full details, else Apply Now --}}
                                                    <a href="{{ $variant->buttonHref() }}" @if (str_starts_with((string) $variant->buttonHref(), 'http')) target="_blank" rel="noopener noreferrer" @endif class="flex items-center gap-2 text-secondary font-medium text-base hover:text-sky-700 hover:no-underline transition-colors group">
                                                        {{ filled($variant->button_text) ? $variant->button_text : __('theme::theme.apply_now') }}
                                                        <x-theme::icon name="arrow-up-right" class="w-4 h-4 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
                                                    </a>
                                                @else
                                                    <a href="{{ $entity->application_url ?: theme_option('header_cta_url') }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 text-secondary font-medium text-base hover:text-sky-700 hover:no-underline transition-colors group">
                                                        {{ __('theme::theme.apply_now') }}
                                                        <x-theme::icon name="arrow-up-right" class="w-4 h-4 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </section>
                    </div>
                @endif

                {{-- Explore Your Major (one block per category) --}}
                @foreach ($page->majorGroups() as $category => $majors)
                    <section class="bg-white scroll-mt-28 relative" id="majors-{{ $loop->iteration }}">
                        <div class="container mx-auto px-4 md:px-0">
                            <div class="mb-6 md:mb-8">
                                <x-theme::heading tag="h2" color="primary">{{ __('theme::theme.program.explore_your', ['name' => $category !== '' ? $category : __('theme::theme.program.major')]) }}</x-theme::heading>
                                @if ($loop->first && ($intro = $entity->intro('majors')))<p class="text-neutral-900 md:text-[24px] mb-4 max-w-5xl">{{ $intro }}</p>@endif
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 gap-4 relative">
                                @foreach ($majors as $major)
                                    <div @class(['group/major relative bg-gray-100 text-center text-base text-gray-800 font-medium py-4 px-3 rounded-sm transition-all duration-300', filled($major->short_description) ? 'cursor-pointer hover:shadow-md hover:bg-blue-50' : 'cursor-default'])
                                         @if (filled($major->short_description)) tabindex="0" @endif>
                                        {{ $major->title }}
                                        @if (filled($major->short_description))
                                            <div @class([
                                                'program-major-tip absolute z-50 w-[280px] bg-white shadow-[0_10px_40px_-10px_rgba(0,0,0,0.2)] rounded-xl p-5 border border-gray-100 text-left hidden group-hover/major:block group-focus/major:block animate-[program-tip_.3s_cubic-bezier(0.16,1,0.3,1)]',
                                                'right-full mr-4 top-1/2 -translate-y-1/2' => $loop->iteration % 4 === 0,
                                                'left-full ml-4 top-1/2 -translate-y-1/2' => $loop->index % 4 === 0,
                                                'left-1/2 -translate-x-1/2 bottom-full mb-3' => $loop->index % 4 !== 0 && $loop->iteration % 4 !== 0,
                                            ]) role="tooltip">
                                                <p class="text-gray-600 text-sm leading-relaxed font-normal">{{ $major->short_description }}</p>
                                                <div @class([
                                                    'absolute w-4 h-4 bg-white rotate-45',
                                                    'top-1/2 -translate-y-1/2 -right-2 border-t border-r border-gray-100' => $loop->iteration % 4 === 0,
                                                    'top-1/2 -translate-y-1/2 -left-2 border-b border-l border-gray-100' => $loop->index % 4 === 0,
                                                    '-bottom-2 left-1/2 -translate-x-1/2 border-b border-r border-gray-100' => $loop->index % 4 !== 0 && $loop->iteration % 4 !== 0,
                                                ])></div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endforeach

                {{-- Accreditations --}}
                @if ($tab = $page->tab('accreditation.program'))
                    <div class="container mx-auto px-5 md:px-0 py-10">
                        <section id="accreditation-program" class="py-10 scroll-mt-28">
                            <div class="mb-8 md:mb-12">
                                <x-theme::heading tag="h2" color="primary">{{ __('theme::theme.program.accreditations') }}</x-theme::heading>
                                @if ($intro = $entity->intro('accreditation'))<p class="text-neutral-900 md:text-[24px] mb-4 max-w-5xl">{{ $intro }}</p>@endif
                            </div>
                            {!! $tab['html'] !!}
                        </section>
                    </div>
                @endif

                {{-- Eligibilities --}}
                @if ($eligibility = $page->eligibility())
                    <div class="bg-white">
                        <section id="eligibilities" class="py-10 scroll-mt-28">
                            <div class="container mx-auto px-4 md:px-0">
                                <div class="mb-6 md:mb-8">
                                    <x-theme::heading tag="h2" color="primary">{{ __('theme::theme.program.eligibilities') }}</x-theme::heading>
                                    @if ($intro = $entity->intro('eligibility'))<p class="text-neutral-900 md:text-[24px] mb-4 max-w-5xl">{{ $intro }}</p>@endif
                                </div>
                                <div class="grid gap-6">
                                    @foreach ($eligibility as $kind => $text)
                                        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-md transition-all duration-300 p-6">
                                            @if ($kind === 'english_medium')<h3 class="text-xl font-semibold text-gray-900 mb-3">{{ __('theme::theme.program.english_medium') }}</h3>@endif
                                            <div class="program-content">{!! $text !!}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </section>
                    </div>
                @endif

                {{-- Professional Memberships --}}
                @if (($memberships = $page->memberships())->isNotEmpty())
                    <div class="bg-gradient-to-b from-gray-50 to-white">
                        <div class="container mx-auto px-5 md:px-0 py-14">
                            <section id="memberships" class="scroll-mt-28">
                                <div class="mb-10 md:mb-14 max-w-5xl">
                                    <x-theme::heading tag="h2" color="primary">{{ __('theme::theme.program.memberships') }}</x-theme::heading>
                                    @if ($intro = $entity->intro('memberships'))<p class="text-neutral-900 md:text-xl leading-relaxed">{{ $intro }}</p>@endif
                                </div>
                                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
                                    @foreach ($memberships as $membership)
                                        <div class="group relative bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col">
                                            <div class="h-48 w-full bg-gray-50 overflow-hidden">
                                                @if ($logo = $membership->firstMedia('logo'))
                                                    <img src="{{ $logo->url('medium') ?? $logo->url() }}" alt="{{ $membership->title }}" loading="lazy" class="w-full h-full object-contain transition-transform duration-500 group-hover:scale-105">
                                                @endif
                                            </div>
                                            <div class="p-6 flex flex-col flex-grow">
                                                <h3 class="text-lg md:text-xl font-semibold text-gray-900 mb-3 leading-snug group-hover:text-secondary transition-colors">{{ $membership->title }}</h3>
                                                <p class="text-neutral-900 text-sm md:text-base leading-relaxed line-clamp-3">{{ $membership->short_description }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        </div>
                    </div>
                @endif

                {{-- Facilities --}}
                @if ($tab = $page->tab('facilities.program'))
                    <section id="facilities-program" class="w-full bg-white py-10 scroll-mt-28">
                        <div class="mx-auto container px-4 md:px-0">
                            <div class="mb-6 sm:mb-8">
                                <x-theme::heading tag="h2" color="primary">{{ __('theme::theme.program.facilities') }}</x-theme::heading>
                                @if ($intro = $entity->intro('facilities'))<p class="text-neutral-900 md:text-[24px] mb-4 max-w-5xl">{{ $intro }}</p>@endif
                            </div>
                            {!! $tab['html'] !!}
                        </div>
                    </section>
                @endif

                {{-- Meet Our Facilitators --}}
                @if (($facilitators = $page->facilitators())->isNotEmpty())
                    <section id="facilitators" class="py-14 bg-gradient-to-b from-white to-gray-50 scroll-mt-28">
                        <div class="container mx-auto px-4">
                            <div class="mb-10 max-w-5xl">
                                <x-theme::heading tag="h2" color="primary">{{ __('theme::theme.program.meet_facilitators') }}</x-theme::heading>
                                <p class="text-neutral-900 md:text-xl leading-relaxed">{{ $entity->intro('facilitators') ?? __('theme::theme.program.facilitators_intro') }}</p>
                            </div>
                            <div class="cursor-grab select-none" x-data="carousel({ perView: { 0: 1, 768: 2, 1024: 3 }, gap: 24, loop: true, autoplay: 4500 })"
                                 @mouseenter="stop()" @mouseleave="start(); up()" @pointerdown="down($event)" @pointermove="move($event)" @pointerup="up()">
                                <div x-ref="viewport" class="overflow-hidden pb-16">
                                    <div x-ref="track" class="flex" :style="trackStyle">
                                        @foreach ($facilitators as $person)
                                            <div class="shrink-0" :style="slideStyle">
                                                <div class="group relative h-[440px] rounded-2xl overflow-hidden shadow hover:shadow-xl transition-all duration-500 bg-neutral-200">
                                                    @if ($photo = $person->firstMedia('photo'))
                                                        <img src="{{ $photo->url('medium') ?? $photo->url() }}" alt="{{ $person->name }}" loading="lazy" draggable="false" class="absolute inset-0 w-full h-full object-cover object-top transition-transform duration-700 group-hover:scale-110">
                                                    @endif
                                                    <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/10 to-transparent"></div>
                                                    @if ($person->type)
                                                        <div class="absolute top-5 left-5 flex items-center gap-2 bg-white/10 backdrop-blur-md text-white text-xs px-3 py-1.5 rounded-full border border-white/20">
                                                            <x-theme::icon name="graduation-cap" class="w-4 h-4 text-white" />
                                                            {{ __("program::program.facilitator_types.{$person->type}") }}
                                                        </div>
                                                    @endif
                                                    <div class="absolute bottom-4 left-4 right-4 bg-white/10 backdrop-blur-xl rounded-xl py-2 px-4 border border-white/20 transition-all duration-500 group-hover:bottom-6">
                                                        <h3 class="text-xl font-bold text-white leading-tight mb-1">
                                                            @if ($person->profile_url)<a href="{{ $person->profile_url }}" target="_blank" rel="noopener" class="text-white hover:underline">{{ $person->name }}</a>@else{{ $person->name }}@endif
                                                        </h3>
                                                        <p class="text-blue-200 text-sm font-medium">{{ $person->designation }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                @endif

                {{-- Your Future Career --}}
                @if ($careers = $page->careers())
                    <div class="w-full">
                        <section id="future-career" class="scroll-mt-28">
                            <div>
                                <x-theme::heading tag="h2" color="primary">{{ __('theme::theme.program.your_future_career') }}</x-theme::heading>
                                @if ($intro = $entity->intro('career_path'))<p class="text-neutral-900 md:text-[24px] mb-4 max-w-5xl">{{ $intro }}</p>@endif
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                @foreach ($careers as $heading => $items)
                                        <div>
                                            <h3 class="text-2xl font-bold text-gray-600 mb-6">{{ __("theme::theme.program.{$heading}") }}</h3>
                                            <ul class="space-y-3">
                                                @foreach ($items as $item)
                                                    <li class="bg-blue-50/10 p-4 rounded-lg border border-gray-200 hover:border-blue-100 transition duration-200 font-bold text-gray-700 hover:text-secondary">{{ $item->title }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                @endforeach
                            </div>
                        </section>
                    </div>
                @endif

                {{-- Testimonials --}}
                @if ($tab = $page->tab('testimonials.program'))
                    <section id="testimonials-program" class="relative overflow-hidden scroll-mt-28">
                        <div class="container mx-auto px-4 md:px-0 relative z-10">
                            <div class="mb-16 mt-8 md:mt-0">
                                <x-theme::heading tag="h2" color="primary" class="justify-center text-4xl md:text-5xl">{{ __('theme::theme.program.testimonials') }}</x-theme::heading>
                                @if ($intro = $entity->intro('testimonials'))<p class="mt-6 text-neutral-900 md:text-[24px] mb-4 max-w-5xl">{{ $intro }}</p>@endif
                            </div>
                            {!! $tab['html'] !!}
                        </div>
                    </section>
                @endif

                {{-- Tuition Fees --}}
                @if ($tab = $page->tab('tuition.fee'))
                    <div class="bg-white scroll-mt-28" id="tuition-fee">
                        <div class="container mx-auto px-4 md:px-0 py-8">
                            <div class="mb-4">
                                <x-theme::heading tag="h2" color="primary">{{ __('theme::theme.program.tuition_fees') }}</x-theme::heading>
                            </div>
                            {!! $tab['html'] !!}
                        </div>
                    </div>
                @endif

                {{-- Other modules' tabs (Academic Calendar, FAQ …) --}}
                @foreach ($page->otherTabs() as $key => $tab)
                    <section id="{{ str_replace('.', '-', $key) }}" class="py-10 scroll-mt-28">
                        <x-theme::heading tag="h2" color="primary">{{ $tab['label'] }}</x-theme::heading>
                        {!! $tab['html'] !!}
                    </section>
                @endforeach
            </div>
        </div>
    </div>
</div>
