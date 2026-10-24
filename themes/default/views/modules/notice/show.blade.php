{{--
    DIU notice page (DIUFronend app/(public)/noticeboard/[slug]): hero "Notice Details"; a card with the date,
    views and category, View/Download (the first file), the title and the text. Beyond DIU: every
    file with its title, a PDF preview, the reference number and the notice's marks.
    Contract: $notice, $attachments, $base, $locale, $page (NoticePage)
--}}
<x-theme::layout :title="$notice->title">
    @include('theme::partials.diu.hero', [
        'title' => settings('notice.notice_title') ?: __('notice::notice.on_board'),
        'subtitle' => null,
        'image' => media_url(settings('notice.board_image'), 'large') ?? theme_image('page_hero_image', 'images/page-hero.jpg'),
        'breadcrumbs' => [
            ['label' => __('theme::theme.home'), 'url' => url($base ?: '/')],
            ['label' => settings('notice.board_title') ?: __('notice::notice.on_board'), 'url' => $page->boardUrl()],
            ['label' => __('notice::notice.page.details'), 'url' => request()->url()],
        ],
    ])

    <main class="bg-gradient-to-br from-slate-50 via-blue-50/30 to-indigo-50 min-h-screen pb-20">
        <div class="container mx-auto px-4 pt-20 flex justify-center">
            <article class="w-full bg-white/95 rounded-3xl border border-slate-100 p-8 md:p-12">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div class="flex items-center gap-3 text-sm text-slate-500 flex-wrap">
                        <x-theme::icon name="calendar" class="w-4 h-4" />
                        <span>{{ $page->date() }}</span>
                        <span class="hidden md:inline">•</span>
                        <x-theme::icon name="eye" class="w-4 h-4 ml-2" />
                        <span>{{ __('notice::notice.page.views', ['count' => number_format($page->views())]) }}</span>
                        @if ($category = $page->category())
                            <span class="hidden md:inline">•</span>
                            <span class="ml-2 font-medium">{{ $category }}</span>
                        @endif
                        @if ($notice->reference_no)
                            <span class="hidden md:inline">•</span>
                            <span class="ml-2">{{ __('notice::notice.fields.reference_no') }}: {{ $notice->reference_no }}</span>
                        @endif
                        @if ($notice->is_urgent)<span class="text-xs font-semibold bg-red-600 text-white px-2 py-0.5 rounded-full">{{ __('notice::notice.fields.is_urgent') }}</span>@endif
                        @unless ($notice->isOnBoard())<span class="text-xs font-semibold bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ __('notice::notice.archived') }}</span>@endunless
                    </div>
                    @if (($files = $page->files()) !== [])
                        <a href="{{ $files[0]['url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg font-medium hover:bg-slate-700 transition-colors shadow hover:no-underline">
                            <x-theme::icon name="download" class="w-4 h-4" /><span>{{ __('notice::notice.page.download') }}</span>
                        </a>
                    @endif
                </div>

                <h1 class="text-2xl md:text-3xl font-bold text-slate-900 mb-4 leading-tight">{{ $notice->title }}</h1>

                @if ($body = $page->body())
                    <div class="mb-6">@include('theme::partials.page.body', ['body' => $body])</div>
                @elseif ($files === [])
                    <div class="flex items-center gap-2 text-slate-500 mb-6"><x-theme::icon name="file-text" class="w-5 h-5" /><span>{{ __('notice::notice.page.no_content') }}</span></div>
                @endif

                @if ($files !== [])
                    <section class="mt-8 space-y-3" aria-labelledby="notice-files">
                        <h2 id="notice-files" class="text-lg font-semibold text-slate-900">{{ __('notice::notice.page.files') }}</h2>
                        @foreach ($files as $file)
                            <div class="border border-slate-200 rounded-xl p-4" x-data="{ open: {{ $loop->first && $file['pdf'] ? 'true' : 'false' }} }">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="w-10 h-10 rounded-lg bg-blue-50 text-blue-700 text-xs font-bold flex items-center justify-center shrink-0">{{ $file['ext'] }}</span>
                                        <span class="font-medium text-slate-800 break-words">{{ $file['title'] }}</span>
                                    </div>
                                    <div class="flex gap-2">
                                        @if ($file['pdf'])
                                            <button type="button" @click="open = ! open" class="px-3 py-2 rounded-lg text-sm font-medium bg-slate-100 text-slate-700 hover:bg-slate-200 cursor-pointer"
                                                    x-text="open ? @js(__('notice::notice.hide_preview')) : @js(__('notice::notice.preview'))">{{ __('notice::notice.preview') }}</button>
                                        @endif
                                        <a href="{{ $file['url'] }}" download class="px-3 py-2 rounded-lg text-sm font-medium bg-primary text-white hover:no-underline inline-flex items-center gap-1.5"><x-theme::icon name="download" class="w-4 h-4" />{{ __('notice::notice.download') }}</a>
                                    </div>
                                </div>
                                @if ($file['pdf'])
                                    <template x-if="open"><iframe src="{{ $file['url'] }}" title="{{ $file['title'] }}" class="w-full h-[75vh] border-0 rounded-lg mt-4"></iframe></template>
                                @endif
                            </div>
                        @endforeach
                    </section>
                @endif
            </article>
        </div>
    </main>
</x-theme::layout>
