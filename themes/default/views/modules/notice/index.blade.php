{{--
    DIU Notice Board (DIUFronend app/(public)/noticeboard): hero; "All Departments" and a tab per faculty
    with its departments in a menu (on phones a list that opens); beyond DIU a search and the archive;
    then a card per notice — the date in a box, title, where it is from, category, date and time, views.
    Contract: the module's (notices, categories, category, entities, entity, archive, layout, base), $page (NoticeBoardPage)
--}}
<x-theme::layout :title="settings('notice.board_title') ?: __('notice::notice.on_board')">
    @include('theme::partials.diu.hero', [
        'title' => settings('notice.board_title') ?: __('notice::notice.on_board'),
        'subtitle' => null,
        'image' => media_url(settings('notice.board_image'), 'large') ?? theme_image('page_hero_image', 'images/page-hero.jpg'),
        'breadcrumbs' => [['label' => __('theme::theme.home'), 'url' => url($base ?: '/')], ['label' => settings('notice.board_title') ?: __('notice::notice.on_board'), 'url' => $page->boardUrl()]],
    ])

    <div class="min-h-screen bg-gray-50">
        {{-- Places --}}
        <div class="bg-white border-b border-gray-200 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 py-4">
                @if ($name = $page->entityName())
                    <div class="mb-4">
                        <a href="{{ $page->url(['entity' => null]) }}" class="inline-flex items-center gap-2 bg-blue-600 text-white rounded-full px-4 py-2 text-sm font-medium hover:no-underline">
                            <x-theme::icon name="building" class="w-4 h-4" /><span>{{ $name }}</span><x-theme::icon name="close" class="w-3.5 h-3.5" />
                        </a>
                    </div>
                @endif

                <nav class="p-2 md:p-3" aria-label="{{ __('notice::notice.entity') }}">
                    @if (($groups = $page->groups()) !== [])
                        {{-- Desktop: a tab per faculty, its departments on hover / click --}}
                        <div class="hidden md:flex flex-wrap items-center gap-2 mb-3">
                            <a href="{{ $page->url(['entity' => null]) }}"
                               @class(['px-3 md:px-4 py-2 md:py-2.5 rounded-lg text-sm font-semibold transition-all hover:scale-105 hover:no-underline', 'bg-blue-600 text-white shadow-lg shadow-blue-200' => ! $entity, 'bg-white text-gray-700 border border-gray-200 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700' => $entity])>{{ __('notice::notice.page.all_departments') }}</a>
                            @foreach ($groups as $group)
                                <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" @keydown.escape="open = false">
                                    <button type="button" @click="open = ! open" :aria-expanded="open"
                                            @class(['px-3 md:px-4 py-2 md:py-2.5 rounded-lg text-sm font-semibold transition-all hover:scale-105 flex items-center gap-1.5 cursor-pointer', 'bg-blue-600 text-white shadow-lg shadow-blue-200' => $group['current'], 'bg-white text-gray-700 border border-gray-200 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700' => ! $group['current']])>
                                        <span class="text-xs md:text-sm">{{ $group['label'] }}</span>
                                        <span class="text-xs opacity-75 bg-black/10 rounded-full px-1.5 py-0.5">{{ count($group['items']) }}</span>
                                    </button>
                                    <div x-show="open" x-cloak x-transition.opacity class="absolute top-full left-0 pt-2 z-[100]">
                                        <div class="bg-white rounded-xl shadow-2xl border border-gray-100 min-w-[300px] lg:min-w-[350px] max-w-[95vw] lg:max-w-[420px] overflow-hidden">
                                            <div class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 text-white p-4 flex items-center justify-between">
                                                <div class="flex-1 min-w-0">
                                                    <h4 class="font-bold text-base truncate">{{ $group['name'] }}</h4>
                                                    <p class="text-blue-100 text-sm mt-1">{{ __('notice::notice.page.select_from', ['count' => count($group['items'])]) }}</p>
                                                </div>
                                                <div class="bg-white/20 rounded-lg p-2 ml-3 shrink-0"><x-theme::icon name="graduation-cap" class="w-6 h-6" /></div>
                                            </div>
                                            <div class="p-3 max-h-72 overflow-y-auto space-y-1">
                                                @foreach ($group['items'] as $item)
                                                    <a href="{{ $item['url'] }}" @class(['block w-full text-left px-4 py-3 rounded-lg transition-all border-2 hover:no-underline', 'bg-blue-50 border-blue-200 text-blue-700 shadow-sm' => $item['current'], 'hover:bg-gray-50 border-transparent text-gray-700 hover:border-gray-200 hover:shadow-sm' => ! $item['current']])>
                                                        <span class="block font-semibold text-sm mb-1">{{ $item['name'] }}</span>
                                                        @if ($item['short'])<span class="block text-xs text-gray-500 font-medium">{{ $item['short'] }}</span>@endif
                                                        @if ($item['current'])<span class="mt-2 inline-flex items-center text-xs text-blue-600 font-medium bg-blue-100 px-2 py-1 rounded-full">✓ {{ __('notice::notice.page.selected') }}</span>@endif
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Phones: the faculties as a list that opens --}}
                        <div class="md:hidden">
                            <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-200">
                                <div class="flex items-center gap-2"><span class="w-2 h-2 bg-blue-600 rounded-full"></span><span class="text-sm font-semibold text-gray-900">{{ __('notice::notice.page.browse') }}</span></div>
                                @if ($entity)<a href="{{ $page->url(['entity' => null]) }}" class="text-xs font-semibold text-blue-600">{{ __('notice::notice.page.all_departments') }}</a>@endif
                            </div>
                            <div class="space-y-3">
                                @foreach ($groups as $group)
                                    <div x-data="{ open: @js($group['current']) }" @class(['rounded-xl border-2 transition-all', 'border-blue-300 bg-blue-50' => $group['current'], 'border-gray-200 bg-white' => ! $group['current']])>
                                        <button type="button" @click="open = ! open" :aria-expanded="open" class="w-full flex items-center justify-between p-4 text-left cursor-pointer">
                                            <span class="min-w-0">
                                                <span class="block font-bold text-sm text-gray-900">{{ $group['label'] }}</span>
                                                <span class="block text-xs text-gray-500 truncate">{{ $group['name'] }}</span>
                                            </span>
                                            <span class="flex items-center gap-2 shrink-0">
                                                <span class="text-xs bg-gray-100 text-gray-600 rounded-full px-2 py-0.5">{{ count($group['items']) }}</span>
                                                <x-theme::icon name="chevron-down" class="w-4 h-4 text-gray-500 transition-transform" ::class="open && 'rotate-180'" />
                                            </span>
                                        </button>
                                        <div x-show="open" x-cloak class="px-3 pb-3 space-y-1">
                                            @foreach ($group['items'] as $item)
                                                <a href="{{ $item['url'] }}" @class(['block px-3 py-2.5 rounded-lg text-sm hover:no-underline', 'bg-blue-600 text-white font-semibold' => $item['current'], 'text-gray-700 hover:bg-gray-50' => ! $item['current']])>{{ $item['name'] }}</a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Beyond DIU: find a notice, the archive (FR-NOT-03/04) --}}
                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        <form method="get" action="{{ $page->boardUrl() }}" class="relative flex-1 min-w-[220px] max-w-md" role="search">
                            @foreach (['archive' => $archive ? 1 : null, 'category' => $category?->slug(), 'entity' => $entity] as $key => $value)
                                @if ($value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                            @endforeach
                            <x-theme::icon name="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('notice::notice.search_placeholder') }}" aria-label="{{ __('notice::notice.search') }}"
                                   class="w-full h-10 pl-9 pr-3 text-sm rounded-lg border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none">
                        </form>
                        <a href="{{ $page->url(['archive' => $archive ? null : 1]) }}" @class(['h-10 px-4 inline-flex items-center gap-2 rounded-lg text-sm font-semibold border hover:no-underline', 'bg-blue-600 text-white border-blue-600' => $archive, 'bg-white text-gray-700 border-gray-200 hover:border-blue-300 hover:text-blue-700' => ! $archive])>
                            <x-theme::icon :name="$archive ? 'clipboard' : 'archive'" class="w-4 h-4" />{{ $archive ? __('notice::notice.page.board') : __('notice::notice.page.archive') }}
                        </a>
                    </div>
                </nav>
            </div>
        </div>

        {{-- Notices --}}
        <div class="max-w-7xl mx-auto px-4 py-6 md:py-8">
            @if (($items = $page->items()) === [])
                <div class="text-center py-12">
                    <x-theme::icon name="file-text" class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ __('notice::notice.page.none') }}</h3>
                    <p class="text-gray-600">{{ $page->entityName() ? __('notice::notice.page.none_in', ['name' => $page->entityName()]) : __('notice::notice.page.none_text') }}</p>
                </div>
            @else
                <div class="space-y-4 mb-8">
                    @foreach ($items as $item)
                        <article class="bg-white rounded-lg shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-200 overflow-hidden">
                            <div class="flex flex-col sm:flex-row">
                                <div class="shrink-0 sm:w-24 bg-blue-900 text-white flex items-center justify-center p-4 sm:p-6 border-2 border-dashed border-white/30">
                                    <div class="flex sm:flex-col items-center gap-2 sm:gap-0">
                                        <div class="text-2xl sm:text-3xl font-bold leading-none">{{ $item['day'] }}</div>
                                        <div class="text-xs sm:text-sm sm:mt-1">{{ $item['month'] }}</div>
                                    </div>
                                </div>
                                <div class="flex-1 p-4 md:p-6 min-w-0">
                                    <h2 class="text-base md:text-lg font-semibold text-gray-900 mb-3">
                                        <a href="{{ $item['url'] }}" class="text-inherit hover:text-blue-600 hover:no-underline transition-colors">{{ $item['title'] }}</a>
                                        @if ($item['pinned'])<span class="ml-2 align-middle text-xs font-semibold bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">{{ __('notice::notice.fields.is_pinned') }}</span>@endif
                                        @if ($item['urgent'])<span class="ml-2 align-middle text-xs font-semibold bg-red-600 text-white px-2 py-0.5 rounded-full">{{ __('notice::notice.fields.is_urgent') }}</span>@endif
                                        @if ($item['new'])<span class="ml-2 align-middle text-xs font-semibold bg-green-100 text-green-700 px-2 py-0.5 rounded-full">{{ __('notice::notice.new') }}</span>@endif
                                    </h2>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:flex lg:flex-wrap items-start lg:items-center gap-3 lg:gap-6 text-xs md:text-sm text-gray-600">
                                        @if ($item['places'] !== '')<div class="flex items-center gap-2"><x-theme::icon name="building" class="w-4 h-4 text-gray-400 shrink-0" /><span class="line-clamp-1">{{ $item['places'] }}</span></div>@endif
                                        @if ($item['category'])<div class="flex items-center gap-2"><x-theme::icon name="file-text" class="w-4 h-4 text-gray-400 shrink-0" /><span>{{ $item['category'] }}</span></div>@endif
                                        <div class="flex items-center gap-2"><x-theme::icon name="calendar" class="w-4 h-4 text-gray-400 shrink-0" /><span>{{ $item['date'] }}</span></div>
                                        <div class="flex items-center gap-2"><x-theme::icon name="eye" class="w-4 h-4 text-gray-400 shrink-0" /><span>{{ $item['views'] }}</span></div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($notices->lastPage() > 1)
                    <div class="mt-8 flex justify-center">
                        <div class="bg-white rounded-lg border border-gray-200 p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="text-sm text-gray-600">
                                {{ __('notice::notice.page.page_of', ['page' => $notices->currentPage(), 'last' => $notices->lastPage()]) }}<span class="hidden sm:inline"> • {{ __('notice::notice.page.total', ['count' => $notices->total()]) }}</span>
                            </div>
                            {{ $notices->onEachSide(1)->links('theme::partials.diu.pagination') }}
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-theme::layout>
