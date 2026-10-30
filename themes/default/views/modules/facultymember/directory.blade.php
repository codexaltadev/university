{{--
    /faculty-members and {faculty|department}/faculty-members in DIU's look (its Academics pages; DIU's main site links out
    to the faculty website for teachers): hero; on the main site "Browse by Faculty" with their teachers; a search with
    department and designation; a DeanCard-style card per teacher, "View Profile" opening the faculty website.
    Title, picture and text from Settings › Faculty Members.
    Contract: the module's (members, faculties, departments, designations, layout, intro, listing, place), $page (FacultyMembersPage)
--}}
<x-theme::layout :title="$page->title()">
    @include('theme::partials.diu.hero', [
        'title' => $page->title(),
        'subtitle' => $page->subtitle() ? e($page->subtitle()) : null,
        'image' => $page->image() ?? theme_image('page_hero_image', 'images/page-hero.webp'),
        'breadcrumbs' => $page->breadcrumbs(),
    ])

    <div class="min-h-screen bg-white" x-data="{ more: false }">
        <div class="container mx-auto px-6 py-12">
            <div class="flex flex-col lg:flex-row gap-12">
                @if (($sections = $page->sections()) !== [])
                    @include('theme::partials.diu.browse-sidebar', ['items' => $sections, 'quickLinks' => [], 'width' => 'lg:w-[320px]'])
                @endif

                <div class="flex-1 min-w-0">
                    @if ($intro = $page->intro())
                        <div class="prose max-w-none mb-6">{!! $intro !!}</div>
                    @endif

                    <form method="get" action="{{ $page->listingUrl() }}" role="search" class="flex flex-col md:flex-row gap-3 mb-4">
                        @if (request('faculty'))<input type="hidden" name="faculty" value="{{ request('faculty') }}">@endif
                        <div class="relative flex-1 flex items-center">
                            <x-theme::icon name="search" class="absolute left-3.5 w-5 h-5 text-gray-400 pointer-events-none" />
                            <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('facultymember::facultymember.page.search_placeholder') }}" aria-label="{{ __('facultymember::facultymember.search') }}"
                                   class="w-full pl-11 pr-4 py-3 text-sm sm:text-base rounded-xl border border-gray-200 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary placeholder-gray-400">
                        </div>
                        @if (($departments = $page->departmentOptions()) !== [])
                            <select name="department" onchange="this.form.submit()" aria-label="{{ __('facultymember::facultymember.fields.department') }}" class="md:w-56 py-3 px-4 rounded-xl border border-gray-200 bg-white shadow-sm text-sm sm:text-base text-gray-700">
                                <option value="">{{ __('facultymember::facultymember.page.all_departments') }}</option>
                                @foreach ($departments as $short => $name)<option value="{{ $short }}" @selected(strcasecmp((string) request('department'), (string) $short) === 0)>{{ $name }}</option>@endforeach
                            </select>
                        @endif
                        @if (($designations = $page->designationOptions()) !== [])
                            <select name="designation" onchange="this.form.submit()" aria-label="{{ __('facultymember::facultymember.fields.designation') }}" class="md:w-52 py-3 px-4 rounded-xl border border-gray-200 bg-white shadow-sm text-sm sm:text-base text-gray-700">
                                <option value="">{{ __('facultymember::facultymember.page.all_designations') }}</option>
                                @foreach ($designations as $id => $name)<option value="{{ $id }}" @selected((string) request('designation') === (string) $id)>{{ $name }}</option>@endforeach
                            </select>
                        @endif
                        <noscript><button type="submit" class="py-3 px-5 rounded-xl bg-secondary text-white">{{ __('facultymember::facultymember.search') }}</button></noscript>
                    </form>
                    <p class="mb-6 text-sm text-gray-500">{{ trans_choice('facultymember::facultymember.page.count', $page->total(), ['count' => $page->total()]) }}</p>

                    @if (($cards = $page->cards()) === [])
                        <div class="text-center py-12 text-gray-500">
                            <x-theme::icon name="search" class="w-10 h-10 mx-auto mb-3 text-gray-300" />
                            <p class="text-lg font-medium">{{ __('facultymember::facultymember.none') }}</p>
                        </div>
                    @else
                        <div @class(['grid grid-cols-1 sm:grid-cols-2 gap-6', 'xl:grid-cols-3' => $sections !== [], 'lg:grid-cols-3 xl:grid-cols-4' => $sections === []])>
                            @foreach ($cards as $card)
                                @include('theme::partials.faculty-member.card', ['card' => $card])
                            @endforeach
                        </div>
                        {{ $members->links('theme::partials.diu.pagination') }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-theme::layout>
