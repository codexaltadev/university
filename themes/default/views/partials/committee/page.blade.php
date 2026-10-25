{{--
    DIU's /committee/{slug} (DIUFronend app/(public)/committee): hero with the committee's name; on the left a white card with a
    blue head "Committee Categories" and every running committee with its members (the shown one blue), "N Committees
    Available"; on phones a select instead; on the right the term, text, then a table with a blue head (#, name with initials,
    committee position as a pill, designation) or cards on phones; the formation order and earlier terms after.
    Contract: $page (CommitteesPage)
--}}
<x-theme::layout :title="$page->current()?->name ?? __('committee::committee.plural')">
    @include('theme::partials.diu.hero', [
        'title' => $page->current()?->name ?? __('committee::committee.plural'),
        'subtitle' => null,
        'image' => theme_image('page_hero_image', 'images/page-hero.jpg'),
        'breadcrumbs' => $page->breadcrumbs(),
    ])

    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-white">
        <div class="container mx-auto px-4 sm:px-6 lg:px-0 py-10 md:py-12">
            @if (($sidebar = $page->sidebar()) === [])
                <p class="text-center text-gray-500 py-12">{{ __('committee::committee.none') }}</p>
            @else
                {{-- Phones: a select (DIU) --}}
                <div class="lg:hidden mb-6">
                    <label for="committee-select" class="block text-sm font-medium text-gray-700 mb-2">{{ __('committee::committee.page.select') }}</label>
                    <select id="committee-select" onchange="window.location = this.value" class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm bg-white text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        @foreach ($sidebar as $group)
                            @foreach ($group['items'] as $item)
                                <option value="{{ $item['url'] }}" @selected($item['current'])>{{ $item['name'] }} ({{ trans_choice('committee::committee.page.member_count', $item['count'], ['count' => $item['count']]) }})</option>
                            @endforeach
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-5 gap-10 lg:gap-8">
                    <aside class="hidden lg:block lg:col-span-1" aria-labelledby="committee-categories">
                        <div class="sticky top-32 max-h-[calc(100vh-10rem)]">
                            <div class="bg-white rounded-xl shadow-lg border border-gray-200 h-full max-h-[calc(100vh-10rem)] flex flex-col overflow-hidden">
                                <div class="border-b border-gray-200 p-6 shrink-0 bg-gradient-to-r from-primary to-blue-600">
                                    <h2 id="committee-categories" class="text-xl font-semibold text-white">{{ __('committee::committee.page.categories') }}</h2>
                                    <p class="text-sm text-white/80 mt-1">{{ __('committee::committee.page.categories_help') }}</p>
                                </div>
                                <nav class="flex-1 overflow-y-auto p-3 space-y-1">
                                    @foreach ($sidebar as $group)
                                        @if ($group['category'])<p class="px-2 pt-3 pb-1 text-xs font-semibold uppercase tracking-wider text-gray-500">{{ $group['category'] }}</p>@endif
                                        @foreach ($group['items'] as $item)
                                            <a href="{{ $item['url'] }}" @if ($item['current']) aria-current="page" @endif
                                               @class(['group flex items-start justify-between w-full px-4 py-4 text-sm font-medium rounded-lg border transition-all duration-200 hover:no-underline',
                                                       'bg-primary text-white shadow-md border-primary' => $item['current'],
                                                       'text-gray-700 bg-gray-50/50 border-transparent hover:bg-primary/5 hover:text-primary hover:border-primary/20' => ! $item['current']])>
                                                <span class="flex flex-col flex-1 min-w-0">
                                                    <span class="font-medium leading-tight">{{ $item['name'] }}</span>
                                                    <span @class(['text-xs mt-1.5 flex items-center gap-1', 'text-white/80' => $item['current'], 'text-gray-500' => ! $item['current']])>
                                                        <x-theme::icon name="user" class="w-3 h-3 shrink-0" />{{ trans_choice('committee::committee.page.member_count', $item['count'], ['count' => $item['count']]) }}
                                                    </span>
                                                </span>
                                                <x-theme::icon name="chevron-right" class="w-4 h-4 ml-2 shrink-0" />
                                            </a>
                                        @endforeach
                                    @endforeach
                                </nav>
                                <div class="shrink-0 p-3 border-t border-gray-200 bg-gray-50/50">
                                    <p class="text-xs text-gray-500 text-center">{{ trans_choice('committee::committee.page.available', $page->total(), ['count' => $page->total()]) }}</p>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <main class="lg:col-span-4 min-w-0 space-y-6">
                        @if ($committee = $page->current())
                            @if ($committee->termLabel() || $committee->isPrevious() || $page->nextTermUrl())
                                <div class="flex flex-wrap items-center gap-3 text-sm">
                                    @if ($term = $committee->termLabel())<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white border border-gray-200 text-gray-700"><x-theme::icon name="calendar" class="w-4 h-4 text-primary" />{{ __('committee::committee.term') }}: {{ $term }}</span>@endif
                                    @if ($committee->isPrevious())<span class="px-3 py-1.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200">{{ __('committee::committee.previous_term') }}</span>@endif
                                    @if ($next = $page->nextTermUrl())<a href="{{ $next }}" class="font-medium text-primary">{{ __('committee::committee.see_current') }} →</a>@endif
                                </div>
                            @endif
                            @if ($description = $page->description())
                                <div class="prose max-w-none bg-white rounded-2xl border border-gray-100 p-6">{!! $description !!}</div>
                            @endif

                            <div class="bg-white rounded-2xl overflow-hidden border border-gray-100">
                                @if (($members = $page->members()) === [])
                                    <p class="p-8 text-center text-gray-500">{{ __('committee::committee.no_members') }}</p>
                                @else
                                    {{-- Phones and tablets: DIU's cards --}}
                                    <div class="lg:hidden p-4 space-y-4">
                                        @foreach ($members as $member)
                                            <div class="bg-gradient-to-r from-gray-50 to-white border border-gray-200 rounded-xl p-5 shadow-sm">
                                                <div class="flex items-start justify-between gap-3 mb-4">
                                                    <div class="min-w-0">
                                                        <h3 class="text-lg font-semibold text-gray-900">@if ($member['url'])<a href="{{ $member['url'] }}" target="_blank" rel="noopener" class="text-inherit">{{ $member['name'] }}</a>@else{{ $member['name'] }}@endif</h3>
                                                        @if ($member['note'])<p class="text-sm text-gray-500">{{ $member['note'] }}</p>@endif
                                                    </div>
                                                    <span class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center text-primary font-semibold text-sm shrink-0">{{ $member['serial'] }}</span>
                                                </div>
                                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('committee::committee.page.position') }}</p>
                                                <p class="mt-1 mb-3"><span class="inline-flex px-3 py-1 rounded-full text-sm font-medium bg-primary text-white shadow-md">{{ $member['position'] }}</span></p>
                                                @if ($member['designation'])
                                                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('committee::committee.page.designation') }}</p>
                                                    <p class="mt-1 text-sm text-gray-900">{{ $member['designation'] }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- Desktop: DIU's table --}}
                                    <div class="hidden lg:block overflow-x-auto">
                                        <table class="committee-members w-full">
                                            <thead class="bg-gradient-to-r from-primary to-blue-600 text-white">
                                                <tr>
                                                    <th scope="col" class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">#</th>
                                                    <th scope="col" class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">{{ __('committee::committee.fields.name') }}</th>
                                                    <th scope="col" class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">{{ __('committee::committee.page.position') }}</th>
                                                    <th scope="col" class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">{{ __('committee::committee.page.designation') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                @foreach ($members as $member)
                                                    <tr class="hover:bg-gray-50 transition-colors duration-200 group">
                                                        <td class="px-6 py-4 whitespace-nowrap"><span class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center text-primary font-semibold text-sm">{{ $member['serial'] }}</span></td>
                                                        <td class="px-6 py-4">
                                                            <div class="flex items-center gap-3">
                                                                @if ($member['photo'])
                                                                    <img src="{{ $member['photo'] }}" alt="" loading="lazy" class="w-12 h-12 rounded-full object-cover shrink-0">
                                                                @else
                                                                    <span class="w-12 h-12 bg-gradient-to-br from-primary/20 to-primary/10 rounded-full flex items-center justify-center shrink-0 text-primary font-bold text-lg" aria-hidden="true">{{ $member['initials'] }}</span>
                                                                @endif
                                                                <div>
                                                                    <div class="text-lg font-semibold text-gray-900 group-hover:text-primary transition-colors">@if ($member['url'])<a href="{{ $member['url'] }}" target="_blank" rel="noopener" class="text-inherit hover:no-underline">{{ $member['name'] }}</a>@else{{ $member['name'] }}@endif</div>
                                                                    @if ($member['note'])<div class="text-sm text-gray-500">{{ $member['note'] }}</div>@endif
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="px-6 py-4"><span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary text-white shadow-md">{{ $member['position'] }}</span></td>
                                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $member['designation'] ?: '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>

                            @if ($pdf = $page->orderPdfUrl())
                                <p><x-theme::button :href="$pdf" target="_blank" variant="outlineSecondary" rounded="full" padding="px-5 py-2.5" size="text-base"><x-theme::icon name="download" class="w-4 h-4" /> {{ __('committee::committee.download_order') }}</x-theme::button></p>
                            @endif
                            @if (($previous = $page->previousTerms()) !== [])
                                <section class="bg-white rounded-2xl border border-gray-100 p-6">
                                    <h2 class="text-lg font-semibold text-gray-900 mb-3">{{ __('committee::committee.previous_terms') }}</h2>
                                    <ul class="flex flex-wrap gap-2">
                                        @foreach ($previous as $term)
                                            <li><a href="{{ $term['url'] }}" class="inline-flex px-3 py-1.5 rounded-full border border-gray-200 text-sm text-gray-700 hover:border-primary hover:text-primary hover:no-underline">{{ $term['label'] }}</a></li>
                                        @endforeach
                                    </ul>
                                </section>
                            @endif
                        @endif
                    </main>
                </div>
            @endif
        </div>
    </div>
</x-theme::layout>
