{{--
    /rankings as DIU's (DIUFronend app/(public)/rankings): hero "Global Rankings & Achievements"; a grey box "All Ranking" with a
    row per result — logo and title linking to the ranking, the year, its text — or cards on phones; the Quick Links.
    Title, line, picture and links from Settings › Accreditation. Contract: the module's (latest, trends, all, base), $page (RecognitionPage)
--}}
<x-theme::layout :title="$page->text('rankings_title') ?? __('accreditation::accreditation.rankings')">
    @include('theme::partials.diu.hero', [
        'title' => $page->text('rankings_title') ?? __('accreditation::accreditation.rankings'),
        'subtitle' => ($line = $page->text('rankings_text')) ? e($line) : null,
        'image' => $page->image() ?? theme_image('page_hero_image', 'images/page-hero.jpg'),
        'breadcrumbs' => $page->breadcrumbs(__('accreditation::accreditation.rankings'), 'rankings'),
    ])

    <div class="min-h-screen bg-white py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="bg-neutral-100 rounded-lg overflow-hidden">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 px-4 sm:px-6 py-4 sm:py-6">{{ __('accreditation::accreditation.page.all_rankings') }}</h2>

                @if (($rows = $page->rankings()) === [])
                    <p class="px-6 pb-8 text-gray-500">{{ __('accreditation::accreditation.none') }}</p>
                @else
                    <div class="hidden lg:block overflow-x-auto border border-gray-200 m-4 bg-white rounded">
                        <table class="rankings-table w-full border-collapse">
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr id="{{ $row['key'] }}" class="border-b text-lg border-gray-200 last:border-b-0 scroll-mt-32">
                                        <td class="px-6 py-6 border-r border-gray-200">
                                            <div class="flex items-center gap-5">
                                                @if ($row['logo'])
                                                    <img src="{{ $row['logo'] }}" alt="{{ $row['title'] }}" loading="lazy" class="w-[6.25rem] h-[4.18rem] object-contain shrink-0">
                                                @else
                                                    <span class="w-[6.25rem] h-[4.18rem] shrink-0 rounded bg-neutral-100 flex items-center justify-center"><x-theme::icon name="sparkles" class="w-7 h-7 text-gray-300" /></span>
                                                @endif
                                                <div>
                                                    @if ($row['url'])<a href="{{ $row['url'] }}" target="_blank" rel="noopener" class="text-gray-700 font-medium hover:text-blue-600 hover:underline">{{ $row['title'] }}</a>@else<p class="text-gray-700 font-medium">{{ $row['title'] }}</p>@endif
                                                    @if ($row['rank'])<p class="mt-1 inline-flex rounded-full bg-secondary px-2.5 py-0.5 text-xs font-semibold text-white">{{ $row['rank'] }}</p>@endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-6 w-24 border-r border-gray-200"><p class="text-gray-700">{{ $row['year'] }}</p></td>
                                        <td class="px-6 py-6"><div class="prose max-w-none text-gray-700 text-base">{!! $row['text'] !!}</div></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="lg:hidden space-y-3 sm:space-y-4 px-2 sm:px-4 pb-4">
                        @foreach ($rows as $row)
                            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-3 sm:p-4 md:p-6">
                                <div class="flex items-start gap-3 sm:gap-4 mb-3">
                                    @if ($row['logo'])<img src="{{ $row['logo'] }}" alt="{{ $row['title'] }}" loading="lazy" class="w-14 h-11 sm:w-16 sm:h-12 object-contain rounded border border-gray-100 shrink-0">@endif
                                    <div class="flex-1 min-w-0 flex flex-col gap-2">
                                        @if ($row['url'])<a href="{{ $row['url'] }}" target="_blank" rel="noopener" class="text-gray-800 font-semibold text-base sm:text-lg leading-tight break-words">{{ $row['title'] }}</a>@else<h3 class="text-gray-800 font-semibold text-base sm:text-lg leading-tight">{{ $row['title'] }}</h3>@endif
                                        <span class="inline-flex self-start items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">{{ $row['year'] }}@if ($row['rank']) · {{ $row['rank'] }}@endif</span>
                                    </div>
                                </div>
                                @if ($row['text'])<div class="prose prose-sm max-w-none text-gray-600">{!! $row['text'] !!}</div>@endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            @include('theme::partials.diu.quick-links', ['links' => $page->quickLinks()])
        </div>
    </div>
</x-theme::layout>
