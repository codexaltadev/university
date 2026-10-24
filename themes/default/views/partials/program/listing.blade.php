{{--
    DIU /programs (DIUFronend app/(public)/programs/page.jsx): hero "Explore Our Offerings"; "Browse by Faculty"
    (the faculties as tabs, then Settings › Programs: its links and Quick Links, also the title, text and picture); the chosen faculty's name, text, "Visit Faculty
    Page", Undergraduate / Post Graduate with their counts (only those it has) and the table of programs (name,
    short name). On phones: UG / PG, "Program Filters" (a drawer with the faculties) and the faculty's name.
    Switching needs no reload. First faculty: ?faculty=, else the theme option programs_faculty, else the first;
    ?isPostgraduate opens Post Graduate. Graduate and post-graduate levels are "Post Graduate", the rest "Undergraduate".
    Contract: $listed (every listed program, with department.faculty and level), $choices (faculties …), $selected
--}}
<x-theme::layout :title="settings('program.listing_title') ?: __('theme::theme.programs.title')">
    @include('theme::partials.diu.hero', [
        'title' => settings('program.listing_title') ?: __('theme::theme.programs.title'),
        'subtitle' => e(settings('program.listing_text') ?: __('theme::theme.programs.subtitle')),
        'image' => media_url(settings('program.listing_image'), 'large') ?? theme_asset('images/programs-banner.jpg'),
        'breadcrumbs' => [['label' => __('theme::theme.home'), 'url' => url('/')], ['label' => __('theme::theme.programs.crumb'), 'url' => url('/programs')]],
    ])

    <div class="min-h-screen pb-20 md:pb-50"
         x-data="{
            faculty: @js(($selected['faculty'] ?? $choices['faculties']->first(fn ($f) => strcasecmp((string) $f->short_name, (string) theme_option('programs_faculty')) === 0) ?? $choices['faculties']->first())?->short_name),
            level: @js(request()->has('isPostgraduate') ? 'PG' : 'UG'),
            counts: @js($choices['faculties']->mapWithKeys(fn ($f) => [$f->short_name => [
                'UG' => $listed->filter(fn ($p) => $p->department?->faculty_id === $f->id && ! (str_contains((string) $p->level?->slug(), 'graduate') && ! str_contains((string) $p->level?->slug(), 'under')))->count(),
                'PG' => $listed->filter(fn ($p) => $p->department?->faculty_id === $f->id && str_contains((string) $p->level?->slug(), 'graduate') && ! str_contains((string) $p->level?->slug(), 'under'))->count(),
            ]])),
            filters: false,
            pick(f) { this.faculty = f; const c = this.counts[f] || { UG: 0, PG: 0 }; if (! c[this.level]) this.level = c.UG ? 'UG' : 'PG'; this.filters = false },
            init() { this.pick(this.faculty) },
         }">
        <main class="mx-auto container px-4 sm:px-6 lg:px-8 py-4 flex flex-col lg:flex-row md:gap-12">
            {{-- Browse by Faculty (desktop) --}}
            <x-theme::reveal direction="left">
                <aside class="hidden lg:block w-full lg:w-[420px] shrink-0" aria-labelledby="faculty-browse-title">
                    <div class="sticky top-32 space-y-6">
                        <div class="rounded-xl">
                            <h2 id="faculty-browse-title" class="text-xl font-bold border-b p-2 pb-2 border-gray-100 text-neutral-600">{{ __('theme::theme.programs.browse') }} ({{ str_pad((string) ($choices['faculties']->count() + count(link_list(settings('program.side_links')))), 2, '0', STR_PAD_LEFT) }})</h2>
                            <ul class="mt-4 border-l border-gray-400" role="list">
                                @foreach ($choices['faculties'] as $faculty)
                                    <li>
                                        <button type="button" @click="pick(@js($faculty->short_name))" :aria-current="faculty === @js($faculty->short_name) ? 'page' : null"
                                                class="group flex w-full text-[22px] text-neutral-800 font-medium items-center my-3 rounded-none p-[10px] text-left cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-600"
                                                :class="faculty === @js($faculty->short_name) ? 'border-l-3 border-primary bg-blue-50 text-slate-900' : 'border-none text-slate-700 hover:bg-blue-50'">
                                            <span class="truncate">{{ $faculty->name }}</span>
                                        </button>
                                    </li>
                                @endforeach
                                @foreach (link_list(settings('program.side_links')) as $link)   {{-- Settings › Programs --}}
                                    <li>
                                        <a href="{{ $link['url'] }}" @if (str_starts_with($link['url'], 'http') && ! str_starts_with($link['url'], url('/'))) target="_blank" rel="noopener" @endif class="group flex w-full text-[22px] text-slate-700 font-medium items-center my-3 rounded-none p-[10px] text-left hover:bg-blue-50 hover:no-underline">
                                            <span class="truncate">{{ $link['label'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        @include('theme::partials.diu.quick-links', ['links' => link_list(settings('program.quick_links'))])
                    </div>
                </aside>
            </x-theme::reveal>

            <section class="flex-1 min-h-screen h-full" aria-label="{{ __('theme::theme.programs.list') }}">
                <x-theme::reveal direction="right">
                    @foreach ($choices['faculties'] as $faculty)
                        <div x-show="faculty === @js($faculty->short_name)" x-cloak>
                            <div class="hidden md:block">
                                <h3 class="text-neutral-800 font-bold text-xl lg:text-4xl mt-4">{{ __('theme::theme.programs.faculty_of', ['name' => $faculty->name]) }}</h3>
                                <div class="mt-2 text-lg text-black max-w-3xl [&_p]:m-0">{{ trim(html_entity_decode(strip_tags((string) $faculty->overview))) }}</div>
                            </div>
                            <div class="mt-4 border-b md:border-none border-gray-300 pb-3">
                                <div class="hidden md:block">
                                    <a href="{{ url($faculty->url()) }}" target="_blank" rel="noopener" class="inline-flex items-center cursor-pointer gap-2 text-base md:text-[22px] font-semibold relative transition-colors duration-300 group text-secondary hover:text-blue-900 hover:no-underline">
                                        <span class="flex items-center gap-2 relative transition-transform duration-300 group-hover:translate-x-1">
                                            <x-theme::icon name="arrow-up-right" width="24" height="24" class="w-6 h-6 transition-transform duration-300 group-hover:rotate-45 group-hover:scale-110" />
                                            {{ __('theme::theme.programs.visit_faculty') }}
                                            <span class="absolute left-0 bottom-0 h-[2px] bg-current w-0 group-hover:w-full transition-all duration-500 ease-out"></span>
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    {{-- Undergraduate / Post Graduate: only the levels the faculty has --}}
                    <div class="grid grid-cols-2 md:flex w-full flex-row gap-2 md:gap-6 my-5 text-secondary">
                        @foreach (['UG' => 'undergraduate', 'PG' => 'postgraduate'] as $key => $label)
                            <div class="w-full md:w-auto" x-show="(counts[faculty] || {})[@js($key)] > 0">
                                <button type="button" @click="level = @js($key)" :aria-pressed="level === @js($key)"
                                        class="relative inline-flex font-medium transition-all duration-300 cursor-pointer hover:scale-[1.02] active:scale-[0.93] rounded-full px-3 py-[10px] text-sm md:text-lg text-center w-full md:w-auto justify-center"
                                        :class="level === @js($key) ? 'text-white border-0 bg-[linear-gradient(98.74deg,color-mix(in_srgb,var(--color-primary)_80%,transparent)_5.6%,color-mix(in_srgb,var(--color-secondary)_80%,transparent)_100%)]' : 'text-secondary bg-transparent border border-neutral-800 hover:text-white hover:bg-[linear-gradient(40.74deg,color-mix(in_srgb,var(--color-primary)_80%,transparent)_5.6%,color-mix(in_srgb,var(--color-secondary)_80%,transparent)_100%)]'">
                                    <span class="relative z-10">{{ __("theme::theme.programs.{$label}") }}<span class="hidden md:inline">{{ __('theme::theme.programs.program_word') }}</span> (<span x-text="String((counts[faculty] || {})[@js($key)] || 0).padStart(2, '0')"></span>)</span>
                                </button>
                            </div>
                        @endforeach
                    </div>

                    {{-- Phones: the faculty drawer --}}
                    <div class="md:hidden flex items-center justify-between gap-3">
                        <button type="button" @click="filters = true" class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 active:scale-[0.98] cursor-pointer">
                            <x-theme::icon name="sliders" class="h-4 w-4" />
                            {{ __('theme::theme.programs.filters') }}
                        </button>
                    </div>
                    @foreach ($choices['faculties'] as $faculty)
                        <p class="px-4 font-bold text-lg md:hidden mt-2" x-show="faculty === @js($faculty->short_name)" x-cloak>{{ $faculty->name }}</p>
                    @endforeach

                    {{-- The programs --}}
                    <div class="overflow-hidden rounded-xl bg-white mt-4">
                        <div class="hidden md:grid grid-cols-12 bg-white px-4 py-3 text-xs font-semibold text-slate-600 border-b border-slate-200" role="row">
                            <div class="text-xl col-span-9" role="columnheader">{{ __('theme::theme.programs.full_name') }}</div>
                            <div class="text-xl col-span-3 text-right" role="columnheader">{{ __('theme::theme.programs.short_tag') }}</div>
                        </div>
                        <ul role="rowgroup" class="divide-y divide-neutral-500">
                            @foreach ($listed as $program)
                                <li role="row" class="grid grid-cols-1 md:grid-cols-12 mt-4 md:mt-6 p-[10px] hover:bg-slate-50"
                                    x-show="faculty === @js($program->department?->faculty?->short_name) && level === @js(str_contains((string) $program->level?->slug(), 'graduate') && ! str_contains((string) $program->level?->slug(), 'under') ? 'PG' : 'UG')" x-cloak>
                                    <div class="col-span-1 md:col-span-9" role="cell">
                                        <a href="{{ url((string) $program->url()) }}" class="inline-flex items-center gap-2 text-lg md:text-[24px] text-black font-medium hover:no-underline">{{ $program->name }}</a>
                                    </div>
                                    <div class="col-span-1 mt-2 md:col-span-3 md:text-right text-sm md:text-xl text-black font-medium" role="cell">{{ $program->short_name }}</div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </x-theme::reveal>
            </section>
        </main>

        {{-- Program Filters drawer (phones) --}}
        <div class="lg:hidden" x-show="filters" x-cloak>
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40" @click="filters = false" x-transition.opacity></div>
            <div class="fixed bottom-0 left-0 w-full h-[calc(100vh-150px)] bg-white shadow-2xl z-50 flex flex-col rounded-t-2xl" x-transition:enter="transition duration-300" x-transition:enter-start="translate-y-full" x-transition:leave="transition duration-200" x-transition:leave-end="translate-y-full" role="dialog" aria-modal="true" aria-label="{{ __('theme::theme.programs.browse') }}">
                <div class="relative px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                    <div class="absolute left-1/2 -translate-x-1/2 top-2 h-1.5 w-12 rounded-full bg-gray-300"></div>
                    <button type="button" class="inline-flex items-center gap-2 text-gray-700 font-medium cursor-pointer" @click="filters = false">
                        <x-theme::icon name="chevron-down" class="w-5 h-5" />
                        {{ __('theme::theme.close') }}
                    </button>
                </div>
                <div class="px-6 pt-10 pb-16 overflow-y-auto">
                    <h2 class="text-xl sm:text-2xl font-semibold text-gray-600 pb-2 border-b border-gray-300 mb-6">{{ __('theme::theme.programs.browse') }}</h2>
                    <div class="space-y-2 ml-5">
                        @foreach ($choices['faculties'] as $faculty)
                            <button type="button" @click="pick(@js($faculty->short_name))" class="block w-full text-left border-b py-2 border-neutral-200 text-lg font-medium cursor-pointer"
                                    :class="faculty === @js($faculty->short_name) ? 'text-secondary' : 'text-neutral-800'">{{ $faculty->name }}</button>
                        @endforeach
                        @foreach (link_list(settings('program.side_links')) as $link)
                            <a href="{{ $link['url'] }}" class="block border-b py-2 border-neutral-200 text-lg font-medium text-neutral-800 hover:no-underline">{{ $link['label'] }}</a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-theme::layout>
