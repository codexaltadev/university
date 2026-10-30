{{--
    /student-discounts as DIU's /discount-partners/{category} (DIUFronend app/(public)/discount-partners): hero; on the left
    "Browse by Section" — every category with its partners, one chosen — and the Quick Links; on the right a blue head with the
    category, how many partners and a search; a table (organization, discount — "Show More" opens the whole discount and the
    terms — website) or cards on phones. Title, line and links from Settings › Partners.
    Contract: the module's (groups, base), $page (PartnersPage)
--}}
<x-theme::layout :title="$page->text('discounts_title') ?? __('partners::partners.discounts')">
    @include('theme::partials.diu.hero', [
        'title' => $page->text('discounts_title') ?? __('partners::partners.discounts'),
        'subtitle' => ($line = $page->text('discounts_text')) ? e($line) : null,
        'image' => $page->image() ?? theme_image('page_hero_image', 'images/page-hero.webp'),
        'breadcrumbs' => $page->breadcrumbs($page->text('discounts_title') ?? __('partners::partners.discounts'), 'student-discounts'),
    ])

    <div class="min-h-screen bg-white" x-data="{ more: false }">
        <div class="container mx-auto px-6 py-12">
            <div class="flex flex-col lg:flex-row gap-12">
                @if (($categories = $page->categories()) !== [])
                    @include('theme::partials.diu.browse-sidebar', ['items' => $categories, 'quickLinks' => $page->quickLinks(), 'width' => 'lg:w-[360px]'])
                @endif

                <div class="flex-1 min-w-0">
                    <div class="bg-gradient-to-r from-primary to-blue-600 rounded-2xl p-8 mb-8 text-white">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div>
                                <h2 class="text-3xl lg:text-4xl font-bold mb-2 text-white">{{ $page->categoryName() ?? ($page->text('discounts_title') ?? __('partners::partners.discounts')) }}</h2>
                                <p class="text-blue-100 text-lg">{{ trans_choice('partners::partners.page.offering', count($rows = $page->discounts()), ['count' => count($rows)]) }}</p>
                            </div>
                            <form method="get" action="{{ $page->url('student-discounts') }}" role="search" class="relative w-full max-w-md">
                                @if (request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif
                                <x-theme::icon name="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-blue-200 w-5 h-5" />
                                <input type="search" name="q" value="{{ $page->search() }}" placeholder="{{ __('partners::partners.page.search') }}" aria-label="{{ __('partners::partners.page.search') }}"
                                       class="w-full pl-10 pr-4 py-3 rounded-xl border border-white/20 bg-white/10 text-white placeholder-blue-200 focus:outline-none focus:ring-2 focus:ring-white/30">
                            </form>
                        </div>
                    </div>

                    @if ($rows === [])
                        <p class="text-center py-12 text-gray-500">{{ __('partners::partners.no_discounts') }}</p>
                    @else
                        <div class="hidden lg:block bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-200">
                            <table class="discount-partners w-full">
                                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                                    <tr>
                                        <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider">{{ __('partners::partners.page.organization') }}</th>
                                        <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider">{{ __('partners::partners.page.benefits') }}</th>
                                        <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider">{{ __('partners::partners.page.website') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($rows as $row)
                                        <tr class="hover:bg-gray-50 transition-colors duration-200 group align-top" x-data="{ open: false }">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    @if ($row['logo'])<img src="{{ $row['logo'] }}" alt="" loading="lazy" class="w-10 h-10 object-contain rounded bg-gray-50">@endif
                                                    <h3 class="text-sm font-semibold text-gray-900 group-hover:text-primary transition-colors">{{ $row['name'] }}</h3>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="max-w-md">
                                                    <p class="text-sm text-gray-700" x-show="! open">{{ $row['short'] }}</p>
                                                    @if ($page->hasMore($row))
                                                        <div x-show="open" x-cloak class="bg-blue-50/50 rounded-lg p-4 border border-blue-200 text-sm text-gray-700 space-y-3">
                                                            <h4 class="font-semibold text-gray-900 flex items-center gap-2"><x-theme::icon name="tag" class="w-4 h-4 text-primary" />{{ __('partners::partners.page.details') }}</h4>
                                                            <p>{{ $row['discount'] }}</p>
                                                            @if ($row['terms'])<p class="pt-3 border-t border-gray-200">{{ $row['terms'] }}</p>@endif
                                                        </div>
                                                        <button type="button" x-on:click="open = ! open" class="text-xs text-primary hover:underline mt-1 inline-flex items-center gap-1">
                                                            <x-theme::icon name="chevron-down" class="w-3 h-3 transition-transform" ::class="open && 'rotate-180'" />
                                                            <span x-text="open ? @js(__('partners::partners.page.show_less')) : @js(__('partners::partners.page.show_more'))">{{ __('partners::partners.page.show_more') }}</span>
                                                        </button>
                                                    @endif
                                                    @if ($row['until'])<p class="mt-1 text-xs text-amber-700">{{ __('partners::partners.page.valid_until', ['date' => $row['until']]) }}</p>@endif
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                @if ($row['url'])<a href="{{ $row['url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm text-primary hover:underline"><x-theme::icon name="globe" class="w-4 h-4" />{{ __('partners::partners.page.visit') }}</a>@endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="lg:hidden space-y-4">
                            @foreach ($rows as $row)
                                <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $row['name'] }}</h3>
                                    @if ($row['discount'])
                                        <div class="mb-4 p-3 bg-blue-50 rounded-lg">
                                            <h4 class="font-medium text-gray-900 mb-2 flex items-center gap-2"><x-theme::icon name="tag" class="w-4 h-4 text-primary" />{{ __('partners::partners.page.benefits') }}</h4>
                                            <p class="text-sm text-gray-700">{{ $row['discount'] }}</p>
                                            @if ($row['terms'])<p class="mt-2 text-xs text-gray-600">{{ $row['terms'] }}</p>@endif
                                        </div>
                                    @endif
                                    @if ($row['url'])<a href="{{ $row['url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm text-primary"><x-theme::icon name="globe" class="w-4 h-4" />{{ __('partners::partners.page.visit') }}</a>@endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-theme::layout>
