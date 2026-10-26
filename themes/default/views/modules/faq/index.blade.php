{{--
    /faq and /{entity}/faq as DIU's /faq: the big centred title with its last word in blue and the line; "Categories" on the left
    (All and each category with its count, the one shown in black; a drawer on phones); a search; each category's questions in a
    white card ("32 questions") opening with + / −, "Was this helpful?" when Settings › FAQ shows it.
    Contract: the module's (groups, selected, faqs, layout, jsonLd, helpful), $page (FaqPage)
--}}
<x-theme::layout :title="__('faq::faq.title')">
    <div class="min-h-screen bg-gray-50" style="margin-top: calc(-1 * var(--header-h, 0px))"
         x-data="{ q: '', side: false, open: null, match(s) { const t = this.q.trim().toLowerCase(); return ! t || t.split(/\s+/).every(w => s.includes(w)) } }">
        <div class="relative bg-white border-b border-gray-100 pt-24">
            <div class="max-w-7xl mx-auto px-4 py-16 sm:py-20 lg:py-24 sm:px-6 lg:px-8 text-center">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-gray-900 mb-6 tracking-tight">
                    @if ($page->title()[0] !== '')<span class="block">{{ $page->title()[0] }}</span>@endif
                    <span class="block text-blue-600">{{ $page->title()[1] }}</span>
                </h1>
                @if ($text = $page->text())<p class="text-lg sm:text-xl text-gray-600 max-w-3xl mx-auto mb-8 leading-relaxed">{{ $text }}</p>@endif
                <label class="relative block max-w-2xl mx-auto">
                    <span class="sr-only">{{ __('faq::faq.search') }}</span>
                    <x-theme::icon name="search" class="absolute left-4 top-1/2 -translate-y-1/2 size-5 text-gray-400" />
                    <input type="search" x-model="q" placeholder="{{ __('faq::faq.search') }}" autocomplete="off"
                           class="w-full rounded-full border border-gray-200 bg-white pl-12 pr-5 py-3.5 text-base shadow-sm outline-none focus:ring-2 focus:ring-blue-500/30">
                </label>
            </div>
        </div>

        <div class="max-w-7xl mx-auto lg:flex">
            @if (count($categories = $page->categories()) > 1)
                <button type="button" x-on:click="side = true" class="lg:hidden m-4 inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 cursor-pointer">
                    <x-theme::icon name="menu" class="size-4" /> {{ __('faq::faq.page.categories') }}
                </button>
                <div x-show="side" x-cloak x-on:click="side = false" class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>
                <aside x-bind:class="side ? 'translate-x-0' : '-translate-x-full'"
                       class="fixed lg:sticky top-0 lg:top-24 left-0 z-40 lg:z-auto w-80 h-screen lg:h-auto lg:max-h-[calc(100vh-7rem)] bg-white border-r border-gray-200 transition-transform duration-300 overflow-y-auto lg:translate-x-0 shrink-0">
                    <div class="p-6 lg:pt-8">
                        <div class="flex items-center justify-between mb-6 lg:hidden">
                            <h2 class="text-lg font-semibold text-gray-900">{{ __('faq::faq.page.categories') }}</h2>
                            <button type="button" x-on:click="side = false" class="p-2 text-gray-600 cursor-pointer" aria-label="{{ __('theme::theme.close') }}"><x-theme::icon name="close" class="size-5" /></button>
                        </div>
                        <h2 class="hidden lg:block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">{{ __('faq::faq.page.categories') }}</h2>
                        <nav class="space-y-2" aria-label="{{ __('faq::faq.page.categories') }}">
                            @foreach ($categories as $c)
                                <a href="{{ $c['url'] }}" @if ($c['current']) aria-current="page" @endif
                                   @class(['w-full px-4 py-3 text-sm font-medium rounded-lg transition-all flex items-center justify-between gap-3 hover:no-underline', 'bg-gray-900 text-white' => $c['current'], 'text-gray-700 hover:bg-gray-100' => ! $c['current']])>
                                    <span>{{ $c['label'] }}</span>
                                    <span @class(['text-xs px-2 py-1 rounded-full', 'bg-white text-gray-900' => $c['current'], 'bg-gray-200 text-gray-600' => ! $c['current']])>{{ $c['count'] }}</span>
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </aside>
            @endif

            <main class="flex-1 min-w-0 px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                @foreach ($page->groups() as $group)
                    <section class="bg-white rounded-xl border border-gray-200 overflow-hidden" x-show="[...$el.querySelectorAll('[data-search]')].some(e => match(e.dataset.search))">
                        <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                            <h2 class="text-lg font-semibold text-gray-900">{{ $group['name'] }}</h2>
                            @if ($group['description'])<div class="text-sm text-gray-600 mt-1">{!! $group['description'] !!}</div>@endif
                            <p class="text-sm text-gray-500 mt-1">{{ trans_choice('faq::faq.page.questions', $group['count'], ['count' => $group['count']]) }}</p>
                        </div>
                        <div class="divide-y divide-gray-200">
                            @foreach ($group['faqs'] as $faq)
                                <div id="{{ $faq['anchor'] }}" class="scroll-mt-28" data-search="{{ $faq['search'] }}" x-show="match($el.dataset.search)">
                                    <button type="button" x-on:click="open = open === {{ $faq['id'] }} ? null : {{ $faq['id'] }}" x-bind:aria-expanded="open === {{ $faq['id'] }}"
                                            class="w-full px-6 py-5 text-left flex items-start justify-between gap-4 hover:bg-gray-50 transition-colors cursor-pointer">
                                        <span class="text-base font-medium text-gray-900 pr-4">{{ $faq['question'] }}</span>
                                        <span class="relative shrink-0 size-5 mt-0.5 text-gray-400" aria-hidden="true">
                                            <span class="absolute left-0 top-1/2 h-0.5 w-5 -translate-y-1/2 bg-current"></span>
                                            <span class="absolute left-1/2 top-0 h-5 w-0.5 -translate-x-1/2 bg-current transition-transform" x-bind:class="open === {{ $faq['id'] }} && 'scale-y-0'"></span>
                                        </span>
                                    </button>
                                    <div x-show="open === {{ $faq['id'] }}" x-collapse x-cloak>
                                        <div class="px-6 pb-6 text-gray-600 leading-relaxed">
                                            @include('theme::partials.page.body', ['body' => $faq['answer']])
                                            @if ($page->helpful())@include('faq::partials.helpful', ['faq' => $faq['model']])@endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
                <p x-cloak x-show="q.trim() !== '' && ! [...$root.querySelectorAll('[data-search]')].some(e => match(e.dataset.search))" class="text-center py-10 text-gray-500">{{ __('faq::faq.no_match') }}</p>
            </main>
        </div>
    </div>
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
</x-theme::layout>
