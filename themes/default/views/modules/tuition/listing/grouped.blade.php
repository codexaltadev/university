{{--
    /tuition-fees as DIU's /tuition-fees and /int-tuition-fees: hero; the notice (yellow); the fee categories (Local /
    International), the levels (All Programs, Undergraduate Program …) and a program search; "Local Tuition Fee (BDT)"; a
    folding card per faculty with DIU's blue-headed table (cards on phones) and Quick Links; "Need Assistance?" links.
    Texts and links from Settings › Tuition Fees. Contract: the module's (category, categories, groups, choices, selected, options), $page (TuitionPage)
--}}
<x-theme::layout :title="$page->title()">
    @include('theme::partials.diu.hero', [
        'title' => $page->title(),
        'subtitle' => $page->text() ? e($page->text()) : null,
        'image' => $page->image() ?? theme_image('page_hero_image', 'images/page-hero.webp'),
        'breadcrumbs' => $page->breadcrumbs(),
    ])

    <div class="min-h-screen bg-slate-50" x-data="{
            level: 'all', q: '',
            ok(r) { const t = this.q.trim().toLowerCase(); return (this.level === 'all' || this.level === r[0]) && (! t || t.split(/\s+/).every(w => r[1].includes(w))) },
            any(rows) { return rows.some(r => this.ok(r)) },
         }">
        <div class="container mx-auto px-4 py-8">
            @if ($notice = $page->notice())
                <div class="mb-4 bg-yellow-50 border-l-4 border-yellow-400 px-4 py-3 flex items-center gap-3">
                    <x-theme::icon name="alert-circle" class="h-5 w-5 text-yellow-600 shrink-0" />
                    <p class="text-gray-800 text-base font-semibold">{{ $notice }}</p>
                </div>
            @endif

            @if (count($categories = $page->categories()) > 1)
                <nav class="mb-4 flex flex-wrap gap-2" aria-label="{{ __('tuition::tuition.category.plural') }}">
                    @foreach ($categories as $c)
                        <a href="{{ $c['url'] }}" @if ($c['current']) aria-current="page" @endif
                           @class(['rounded-md px-4 py-2 text-base font-medium hover:no-underline', 'bg-secondary text-white' => $c['current'], 'bg-white text-secondary border border-secondary/30 hover:border-secondary' => ! $c['current']])>{{ $c['label'] }}</a>
                    @endforeach
                </nav>
            @endif

            <div class="flex flex-col gap-3 md:flex-row md:flex-wrap md:items-center">
                <div class="w-full md:w-auto overflow-x-auto">
                    <div class="flex gap-2 whitespace-nowrap" role="tablist">
                        @foreach ($page->levels() as $level)
                            <button type="button" role="tab" x-on:click="level = @js($level['key'])" x-bind:aria-selected="level === @js($level['key'])"
                                    x-bind:class="level === @js($level['key']) ? 'text-white bg-gradient-to-r from-primary to-secondary border-transparent' : 'text-secondary bg-white border-secondary hover:bg-gradient-to-r hover:from-primary hover:to-secondary hover:text-white'"
                                    class="py-2 md:py-[10px] rounded-full border px-3 md:px-4 text-sm md:text-lg transition-colors cursor-pointer">{{ $level['label'] }}</button>
                        @endforeach
                    </div>
                </div>
                <label class="relative w-full md:w-auto md:flex-1 md:min-w-[240px]">
                    <span class="sr-only">{{ __('tuition::tuition.page.search') }}</span>
                    <x-theme::icon name="search" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400" />
                    <input type="search" x-model="q" placeholder="{{ __('tuition::tuition.page.search') }}"
                           class="py-2 md:py-[10px] border border-secondary w-full rounded-full text-sm md:text-base bg-white pl-9 md:pl-10 pr-3 text-zinc-700 placeholder:text-zinc-400 outline-none focus:ring-2 focus:ring-primary/30">
                </label>
            </div>

            <div class="bg-gray-300 w-full h-0.5 my-8"></div>
            @if ($heading = $page->heading())<h2 class="text-neutral-800 font-bold text-xl lg:text-4xl mt-4 mb-6">{{ $heading }}</h2>@endif

            @if (($groups = $page->groups()) === [])
                <p class="text-center py-10 text-zinc-500">{{ __('tuition::tuition.page.none') }}</p>
            @elseif ($cols = $page->columns())
                <div class="mt-8 mb-3 space-y-3">
                    @foreach ($groups as $group)
                        <section class="rounded-lg p-4 bg-neutral-100 shadow" x-data="{ open: @js($loop->first) }"
                                 x-show="any(@js($group['filters']))">
                            <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open" class="flex w-full items-center justify-between px-2 lg:px-4 py-2 lg:py-4 text-left cursor-pointer">
                                <h3 class="text-lg font-bold lg:text-[24px] text-neutral-900">{{ $group['name'] }}</h3>
                                <x-theme::icon name="chevron-down" class="h-8 w-8 text-zinc-500 transition-transform" x-bind:class="open && 'rotate-180'" />
                            </button>
                            <div x-show="open" x-collapse @if (! $loop->first) x-cloak @endif>
                                <div class="border-t border-slate-200 pt-2">
                                    {{-- phones: a card per program --}}
                                    <div class="md:hidden space-y-3">
                                        @foreach ($group['rows'] as $row)
                                            <div class="rounded-lg border border-slate-200 bg-white p-3" x-show="ok(@js($row['filter']))">
                                                <div class="font-semibold text-primary">@if ($row['url'])<a href="{{ $row['url'] }}">{{ $row['name'] }}</a>@else{{ $row['name'] }}@endif</div>
                                                <dl class="mt-2 space-y-1 text-sm text-neutral-600">
                                                    <div class="flex justify-between gap-3"><dt class="text-zinc-500">{{ __('tuition::tuition.page.credits') }}</dt><dd>{{ $row['credits'] }}</dd></div>
                                                    <div class="flex justify-between gap-3"><dt class="text-zinc-500">{{ __('tuition::tuition.page.duration') }}</dt><dd>{{ $row['duration'] }}</dd></div>
                                                    @if ($cols['majors'] && $row['majors'])<div class="flex justify-between gap-3"><dt class="text-zinc-500">{{ __('tuition::tuition.page.majors') }}</dt><dd class="text-right">{{ $row['majors'] }}</dd></div>@endif
                                                    <div class="flex justify-between gap-3"><dt class="text-zinc-500">{{ __('tuition::tuition.page.admission') }}</dt><dd>{{ $row['admission'] }}</dd></div>
                                                    @if ($cols['term'])<div class="flex justify-between gap-3"><dt class="text-zinc-500">{{ __('tuition::tuition.page.term') }}</dt><dd>{{ $row['term'] }}</dd></div>@endif
                                                    @foreach ($cols['years'] as $i => $year)<div class="flex justify-between gap-3"><dt class="text-zinc-500">{{ $year }}</dt><dd>{{ $row['years'][$i] }}</dd></div>@endforeach
                                                    @if ($cols['other'])<div class="flex justify-between gap-3"><dt class="text-zinc-500">{{ __('tuition::tuition.page.other') }}</dt><dd>{{ $row['other'] }}</dd></div>@endif
                                                    <div class="flex justify-between gap-3"><dt class="text-zinc-500">{{ __('tuition::tuition.page.tuition') }}</dt><dd>{{ $row['tuition'] }}</dd></div>
                                                    <div class="flex justify-between gap-3 font-medium text-neutral-900"><dt class="text-zinc-500">{{ __('tuition::tuition.page.total') }}</dt><dd>{{ $row['total'] }}</dd></div>
                                                </dl>
                                            </div>
                                        @endforeach
                                    </div>
                                    {{-- wider screens: DIU's blue-headed table --}}
                                    <div class="hidden md:block overflow-x-auto">
                                        <table class="min-w-full border-separate border-spacing-0 text-base">
                                            <thead>
                                                <tr class="text-lg font-bold text-left text-white">
                                                    <th scope="col" class="px-4 py-3 bg-primary rounded-l-lg min-w-[240px] md:w-[344px]">{{ __('tuition::tuition.page.program') }}</th>
                                                    @if ($cols['majors'])<th scope="col" class="px-4 py-3 bg-primary">{{ __('tuition::tuition.page.majors') }}</th>@endif
                                                    <th scope="col" class="px-4 py-3 bg-primary">{{ __('tuition::tuition.page.credits') }}</th>
                                                    <th scope="col" class="px-4 py-3 bg-primary">{{ __('tuition::tuition.page.duration') }}</th>
                                                    <th scope="col" class="px-4 py-3 bg-primary">{{ __('tuition::tuition.page.admission') }}</th>
                                                    @if ($cols['term'])<th scope="col" class="px-4 py-3 bg-primary">{{ __('tuition::tuition.page.term') }}</th>@endif
                                                    @foreach ($cols['years'] as $year)<th scope="col" class="px-4 py-3 bg-primary">{{ $year }}</th>@endforeach
                                                    @if ($cols['other'])<th scope="col" class="px-4 py-3 bg-primary">{{ __('tuition::tuition.page.other') }}</th>@endif
                                                    <th scope="col" class="px-4 py-3 bg-primary">{{ __('tuition::tuition.page.tuition') }}</th>
                                                    <th scope="col" class="px-4 py-3 bg-primary rounded-r-lg">{{ __('tuition::tuition.page.total') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="text-neutral-600">
                                                @foreach ($group['rows'] as $row)
                                                    <tr class="align-middle" x-show="ok(@js($row['filter']))">
                                                        <td class="px-4 py-4 border-b border-slate-200 text-lg">@if ($row['url'])<a href="{{ $row['url'] }}" class="hover:text-primary">{{ $row['name'] }}</a>@else{{ $row['name'] }}@endif</td>
                                                        @if ($cols['majors'])<td class="px-4 py-4 border-b border-slate-200 max-w-[220px]">{{ $row['majors'] }}</td>@endif
                                                        <td class="px-4 py-4 border-b border-slate-200 whitespace-nowrap">{{ $row['credits'] }}</td>
                                                        <td class="px-4 py-4 border-b border-slate-200 whitespace-nowrap">{{ $row['duration'] }}</td>
                                                        <td class="px-4 py-4 border-b border-slate-200 whitespace-nowrap">{{ $row['admission'] }}</td>
                                                        @if ($cols['term'])<td class="px-4 py-4 border-b border-slate-200 whitespace-nowrap">{{ $row['term'] }}</td>@endif
                                                        @foreach ($cols['years'] as $i => $year)<td class="px-4 py-4 border-b border-slate-200 whitespace-nowrap">{{ $row['years'][$i] }}</td>@endforeach
                                                        @if ($cols['other'])<td class="px-4 py-4 border-b border-slate-200 whitespace-nowrap">{{ $row['other'] }}</td>@endif
                                                        <td class="px-4 py-4 border-b border-slate-200">{{ $row['tuition'] }}</td>
                                                        <td class="px-4 py-4 border-b border-slate-200 font-semibold text-neutral-900">{{ $row['total'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @if (($links = $page->quickLinks()) !== [])
                                        <div class="mt-4 rounded-xl bg-white p-3">@include('theme::partials.diu.quick-links', ['links' => $links])</div>
                                    @endif
                                </div>
                            </div>
                        </section>
                    @endforeach
                </div>
            @endif

            @if (($help = $page->helpLinks()) !== [])
                <div class="mt-10">
                    <h2 class="text-2xl md:text-[34px] font-semibold text-neutral-800">{{ __('tuition::tuition.page.help') }}</h2>
                    <div class="mt-5 mb-20 flex flex-wrap gap-2">
                        @foreach ($help as $link)
                            <x-theme::button :href="$link['url']" variant="outlineSecondary" rounded="full" padding="px-4 py-[10px]" size="text-lg">{{ $link['label'] }}</x-theme::button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-theme::layout>
