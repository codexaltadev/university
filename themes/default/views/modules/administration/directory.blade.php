{{--
    /administration as DIU's /admin/{section} (DIUFronend app/(public)/admin): hero "Administration"; on the left
    "Browse by Section (NN)" — each section with its people, one chosen — and the Quick Links; on the right the
    search over everyone, then a table (photo, name, designation, contact) or, on phones, a card per person.
    Title, picture, text and links from Settings › Administration.
    Contract: the module's (groups, categories, counts, search …), $page (AdministrationPage)
--}}
<x-theme::layout :title="$page->title()">
    @include('theme::partials.diu.hero', [
        'title' => $page->title(),
        'subtitle' => null,
        'image' => $page->image() ?? theme_image('page_hero_image', 'images/page-hero.webp'),
        'breadcrumbs' => [['label' => __('theme::theme.home'), 'url' => url('/')], ['label' => $page->title(), 'url' => $page->listingUrl()]],
    ])

    <div class="min-h-screen bg-white" x-data="{ more: false }">
        <div class="container mx-auto px-6 py-12">
            <div class="flex flex-col lg:flex-row gap-12">
                @include('theme::partials.diu.browse-sidebar', ['items' => $page->sections(), 'quickLinks' => $page->quickLinks()])

                <div class="flex-1 min-w-0">
                    @if ($intro = $page->intro())
                        <div class="prose max-w-none mb-6">{!! $intro !!}</div>
                    @endif

                    <form method="get" action="{{ $page->listingUrl() }}" role="search" class="relative w-full mb-6">
                        <div class="relative flex items-center">
                            <x-theme::icon name="search" class="absolute left-3.5 w-5 h-5 text-gray-400 pointer-events-none" />
                            <input type="search" name="q" value="{{ $page->search() }}" placeholder="{{ __('administration::administration.page.search_placeholder') }}" aria-label="{{ __('administration::administration.search') }}"
                                   class="w-full pl-11 pr-10 py-3 text-sm sm:text-base rounded-xl border border-gray-200 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition-all duration-200 placeholder-gray-400">
                            @if ($page->searching())
                                <a href="{{ $page->listingUrl() }}" class="absolute right-3.5 text-gray-400 hover:text-gray-600" aria-label="{{ __('administration::administration.all') }}"><x-theme::icon name="close" class="w-5 h-5" /></a>
                            @endif
                        </div>
                        @if ($page->searching())
                            <p class="mt-2 text-xs text-gray-500 px-1">{{ trans_choice('administration::administration.page.results', count($page->rows()), ['count' => count($page->rows()), 'q' => $page->search()]) }}</p>
                        @endif
                    </form>

                    @if (($rows = $page->rows()) === [])
                        <div class="text-center py-12 text-gray-500">
                            <x-theme::icon name="search" class="w-10 h-10 mx-auto mb-3 text-gray-300" />
                            <p class="text-lg font-medium">{{ $page->searching() ? __('administration::administration.none') : __('administration::administration.page.empty') }}</p>
                            @if ($page->searching())<p class="text-sm mt-1 text-gray-400">{{ __('administration::administration.page.no_results_help') }}</p>@endif
                        </div>
                    @else
                        {{-- Desktop: DIU's table --}}
                        <div class="hidden lg:block overflow-x-auto">
                            <table class="administration-table min-w-full bg-white shadow-sm rounded-lg overflow-hidden">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th scope="col" class="px-6 py-4 text-left text-lg font-semibold text-gray-900">{{ __('administration::administration.page.administrator') }}</th>
                                        <th scope="col" class="px-6 py-4 text-left text-lg font-semibold text-gray-900">{{ __('administration::administration.fields.designation') }}</th>
                                        <th scope="col" class="px-6 py-4 text-left text-lg font-semibold text-gray-900">{{ __('administration::administration.page.contact') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($rows as $row)
                                        <tr class="hover:bg-gray-50 transition-colors duration-200 group">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-4">
                                                    @include('theme::partials.administration.photo', ['row' => $row, 'size' => 'w-16 h-16 sm:w-20 sm:h-20'])
                                                    <h3 class="text-lg font-semibold text-gray-900 group-hover:text-primary transition-colors">
                                                        @if ($row['url'])<a href="{{ $row['url'] }}" class="text-inherit hover:no-underline">{{ $row['name'] }}</a>@else{{ $row['name'] }}@endif
                                                    </h3>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4"><span class="inline-flex items-center px-3 py-1 rounded-full text-lg font-medium">{{ $row['designation'] }}</span></td>
                                            <td class="px-6 py-4">
                                                <div class="space-y-2">
                                                    @if ($row['email'])<div class="flex items-center gap-2"><x-theme::icon name="mail" class="w-4 h-4 text-gray-400" /><a href="mailto:{{ $row['email'] }}" class="text-lg text-primary hover:underline">{{ $row['email'] }}</a></div>@endif
                                                    @if ($row['phone'])<div class="flex items-center gap-2"><x-theme::icon name="phone" class="w-4 h-4 text-gray-400" /><a href="tel:{{ $row['tel'] }}" class="text-lg text-gray-700 hover:text-primary">{{ $row['phone'] }}</a></div>@endif
                                                    @if ($row['extension'])<p class="text-lg text-gray-700">{{ __('administration::administration.ext') }} {{ $row['extension'] }}</p>@endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Phones and tablets: DIU's cards --}}
                        <div class="lg:hidden space-y-4">
                            @foreach ($rows as $row)
                                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-200 p-4">
                                    <div class="flex items-start gap-4 mb-4">
                                        @include('theme::partials.administration.photo', ['row' => $row, 'size' => 'w-16 h-16 sm:w-20 sm:h-20'])
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-lg font-semibold text-gray-900">@if ($row['url'])<a href="{{ $row['url'] }}" class="text-inherit hover:no-underline">{{ $row['name'] }}</a>@else{{ $row['name'] }}@endif</h3>
                                            @if ($row['designation'])<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium mt-1 bg-blue-100 text-blue-800">{{ $row['designation'] }}</span>@endif
                                        </div>
                                    </div>
                                    <div class="space-y-2 mb-4">
                                        @if ($row['email'])<div class="flex items-center gap-2 min-w-0"><x-theme::icon name="mail" class="w-4 h-4 text-gray-400 shrink-0" /><a href="mailto:{{ $row['email'] }}" class="text-base text-primary hover:underline truncate">{{ $row['email'] }}</a></div>@endif
                                        @if ($row['phone'])<div class="flex items-center gap-2 min-w-0"><x-theme::icon name="phone" class="w-4 h-4 text-gray-400 shrink-0" /><a href="tel:{{ $row['tel'] }}" class="text-base text-gray-700 hover:text-primary truncate">{{ $row['phone'] }}</a>@if ($row['extension'])<span class="text-sm text-gray-500 shrink-0">· {{ __('administration::administration.ext') }} {{ $row['extension'] }}</span>@endif</div>@endif
                                    </div>
                                    @if ($row['email'] || $row['tel'])
                                        <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                                            @if ($row['email'])<a href="mailto:{{ $row['email'] }}" class="p-2 bg-gray-100 hover:bg-green-500 hover:text-white rounded-lg transition-all duration-200" aria-label="{{ $row['email'] }}"><x-theme::icon name="mail" class="w-4 h-4" /></a>@endif
                                            @if ($row['tel'])<a href="tel:{{ $row['tel'] }}" class="p-2 bg-gray-100 hover:bg-blue-500 hover:text-white rounded-lg transition-all duration-200" aria-label="{{ $row['phone'] }}"><x-theme::icon name="phone" class="w-4 h-4" /></a>@endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-theme::layout>
