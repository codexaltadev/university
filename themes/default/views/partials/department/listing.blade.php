{{--
    DIU /departments (DIUFronend app/(public)/departments: page.jsx, SectionHeader, DepartmentCard): hero "All Departments"
    (Settings › Departments: title, text, picture); "Browse by Faculty" with Browse ALL and the faculties, then the Quick
    Links; on the right "All Departments" or the faculty's name, text and "Visit Faculty Page", and the departments as
    grey cards (picture, name, the start of its About). Switching needs no reload; ?faculty= opens one. On phones:
    "Program Filters" opens the faculties in a drawer.
    Contract: $listed (every listed department, with faculty), $faculties, $currentFaculty
--}}
<x-theme::layout :title="settings('department.listing_title') ?: __('theme::theme.departments.title')">
    @include('theme::partials.diu.hero', [
        'title' => settings('department.listing_title') ?: __('theme::theme.departments.title'),
        'subtitle' => e(settings('department.listing_text') ?: __('theme::theme.departments.subtitle')),
        'image' => media_url(settings('department.listing_image'), 'large') ?? theme_asset('images/programs-banner.webp'),
        'breadcrumbs' => [['label' => __('theme::theme.home'), 'url' => url('/')], ['label' => __('theme::theme.departments.crumb'), 'url' => url('/departments')]],
    ])

    <div class="min-h-screen pb-20 md:pb-50" x-data="{ faculty: @js($currentFaculty?->short_name ?? 'all'), filters: false, pick(f) { this.faculty = f; this.filters = false } }">
        <div class="mx-auto container px-4 sm:px-6 lg:px-8 py-4 flex flex-col lg:flex-row md:gap-12">
            {{-- Browse by Faculty (desktop) --}}
            <x-theme::reveal direction="left">
                <aside class="hidden lg:block w-full lg:w-[420px] shrink-0" aria-labelledby="department-browse-title">
                    <div class="sticky top-32 space-y-6">
                        <div class="rounded-xl">
                            <h2 id="department-browse-title" class="text-xl font-bold border-b p-2 pb-2 border-gray-100 text-neutral-600">{{ __('theme::theme.programs.browse') }} ({{ str_pad((string) ($faculties->count() + 1), 2, '0', STR_PAD_LEFT) }})</h2>
                            <ul class="mt-4 border-l border-gray-400" role="list">
                                @foreach ([['all', __('theme::theme.departments.browse_all')], ...$faculties->map(fn ($f) => [$f->short_name, $f->name])] as [$key, $label])
                                    <li>
                                        <button type="button" @click="pick(@js($key))" :aria-current="faculty === @js($key) ? 'page' : null"
                                                class="group flex w-full text-[22px] text-neutral-800 font-medium items-center my-3 rounded-none p-[10px] text-left cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-600"
                                                :class="faculty === @js($key) ? 'border-l-3 border-secondary bg-blue-50 text-slate-900' : 'border-none text-slate-700 hover:bg-blue-50'">
                                            <span class="truncate">{{ $label }}</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        @include('theme::partials.diu.quick-links', ['links' => link_list(settings('department.quick_links'))])
                    </div>
                </aside>
            </x-theme::reveal>

            <section class="flex-1 min-h-screen h-full" aria-label="{{ __('theme::theme.departments.list') }}">
                <x-theme::reveal direction="right">
                    {{-- The heading: All Departments, or the faculty with its text and page --}}
                    <div class="hidden md:block">
                        <h3 class="text-neutral-800 font-bold text-xl lg:text-4xl mt-4" x-show="faculty === 'all'">{{ __('theme::theme.departments.all') }}</h3>
                        @foreach ($faculties as $faculty)
                            <div x-show="faculty === @js($faculty->short_name)" x-cloak>
                                <h3 class="text-neutral-800 font-bold text-xl lg:text-4xl mt-4">{{ $faculty->name }}</h3>
                                @if (filled($text = trim(html_entity_decode(strip_tags((string) $faculty->overview)))))
                                    <div class="mt-2 text-lg text-black max-w-3xl">{{ $text }}</div>
                                @endif
                                <div class="pt-6">
                                    <x-theme::link-button :href="url($faculty->url())" target="_blank" variant="primary">{{ __('theme::theme.programs.visit_faculty') }}</x-theme::link-button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Phones: the faculty drawer --}}
                    <div class="mt-4 border-b md:border-none border-gray-300 pb-3 lg:hidden">
                        <button type="button" @click="filters = true" class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 active:scale-[0.98] cursor-pointer">
                            <x-theme::icon name="sliders" class="h-4 w-4" />
                            {{ __('theme::theme.programs.filters') }}
                        </button>
                        <p class="px-3 font-bold text-lg md:hidden mt-2" x-show="faculty === 'all'">{{ __('theme::theme.departments.all') }}</p>
                        @foreach ($faculties as $faculty)
                            <p class="px-3 font-bold text-lg md:hidden mt-2" x-show="faculty === @js($faculty->short_name)" x-cloak>{{ $faculty->name }}</p>
                        @endforeach
                    </div>

                    {{-- The departments --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-6" role="list">
                        @foreach ($listed as $department)
                            <article class="bg-neutral-100 p-3 sm:p-4 md:p-5 lg:p-6 rounded-lg sm:rounded-xl border border-gray-100 overflow-hidden group transition-all duration-300" role="listitem"
                                     x-show="faculty === 'all' || faculty === @js($department->faculty?->short_name)">
                                <div class="relative pb-3 sm:pb-4 md:pb-5 lg:pb-6">
                                    @if ($photo = $department->firstMedia('photo') ?? $department->firstMedia('cover'))
                                        <img src="{{ $photo->url('medium') }}" alt="" loading="lazy" class="rounded-lg sm:rounded-xl w-full h-[160px] sm:h-[180px] md:h-[200px] lg:h-[220px] object-cover">
                                    @else
                                        <div class="rounded-lg sm:rounded-xl w-full h-[160px] sm:h-[180px] md:h-[200px] lg:h-[220px] bg-neutral-200"></div>
                                    @endif
                                </div>
                                <a href="{{ $department->listingUrl() }}" @if (filled($department->external_url)) target="_blank" rel="noopener" @endif
                                   class="text-lg sm:text-xl md:text-[1.3rem] lg:text-[1.5rem] font-semibold text-neutral-800 block mb-3 sm:mb-4 md:mb-5 lg:mb-6 leading-tight group-hover:underline hover:no-underline">{{ $department->name }}</a>
                                @if (filled($about = trim(html_entity_decode(strip_tags((string) $department->about)))))
                                    <p class="text-sm md:text-base text-neutral-500 leading-relaxed line-clamp-3 md:line-clamp-4 m-0">{{ $about }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </x-theme::reveal>
            </section>
        </div>

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
                        @foreach ([['all', __('theme::theme.departments.browse_all')], ...$faculties->map(fn ($f) => [$f->short_name, $f->name])] as [$key, $label])
                            <button type="button" @click="pick(@js($key))" class="block w-full text-left border-b py-2 border-neutral-200 text-lg font-medium cursor-pointer"
                                    :class="faculty === @js($key) ? 'text-secondary' : 'text-neutral-800'">{{ $label }}</button>
                        @endforeach
                    </div>
                    @include('theme::partials.diu.quick-links', ['links' => link_list(settings('department.quick_links'))])
                </div>
            </div>
        </div>
    </div>
</x-theme::layout>
