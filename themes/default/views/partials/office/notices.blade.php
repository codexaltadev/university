{{--
    An office's notice board in the office's frame, as DIU's /registrar-office/notice (NoticesSection): the blue box
    "Notices & Announcements" — date and time, title — beyond DIU a search and the archive, then the pages.
    Contract: $office (OfficePage), $page (NoticeBoardPage), $notices (paginator), $archive
--}}
<x-theme::office-frame :office="$office" :current="$page->boardUrl()" :title="__('office::office.page.notices').' — '.$office->office->name"
    :crumbs="[['label' => settings('notice.board_title') ?: __('notice::notice.on_board'), 'url' => $page->boardUrl()]]">
    <section class="bg-blue-900 text-white rounded-xl p-6 md:p-8" aria-labelledby="office-notices">
        <h2 id="office-notices" class="text-3xl md:text-5xl font-bold mb-6">{{ $archive ? __('notice::notice.page.archive') : __('office::office.page.notices') }}</h2>

        <div class="flex flex-wrap items-center gap-3 mb-4">
            <form method="get" action="{{ $page->boardUrl() }}" class="relative flex-1 min-w-[220px] max-w-md" role="search">
                @if ($archive)<input type="hidden" name="archive" value="1">@endif
                <x-theme::icon name="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-white/60" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('notice::notice.search_placeholder') }}" aria-label="{{ __('notice::notice.search') }}"
                       class="w-full h-10 pl-9 pr-3 rounded-lg bg-white/10 border border-white/30 text-white placeholder-white/60 text-sm focus:outline-none focus:ring-2 focus:ring-white/60">
            </form>
            <a href="{{ $page->url(['archive' => $archive ? null : 1]) }}" class="h-10 px-4 inline-flex items-center gap-2 rounded-lg text-sm font-semibold border border-white/30 text-white hover:bg-white/10 hover:no-underline">
                <x-theme::icon :name="$archive ? 'clipboard' : 'archive'" class="w-4 h-4" />{{ $archive ? __('notice::notice.page.board') : __('notice::notice.page.archive') }}
            </a>
        </div>

        @if (($items = $page->items()) === [])
            <p class="py-8 text-white/80">{{ __('notice::notice.page.none_text') }}</p>
        @else
            <ul class="divide-y divide-white/20" role="list">
                @foreach ($items as $item)
                    <li>
                        <a href="{{ $item['url'] }}" class="group flex flex-col sm:flex-row sm:items-baseline gap-1 sm:gap-5 py-4 text-white hover:no-underline">
                            <span class="shrink-0 text-base md:text-xl text-white/90">{{ $item['date'] }}</span>
                            <span class="text-lg md:text-2xl group-hover:underline">{{ $item['title'] }}
                                @if ($item['urgent'])<span class="ml-2 align-middle text-xs font-semibold bg-red-600 text-white px-2 py-0.5 rounded-full">{{ __('notice::notice.fields.is_urgent') }}</span>@endif
                                @if ($item['new'])<span class="ml-2 align-middle text-xs font-semibold bg-white text-blue-900 px-2 py-0.5 rounded-full">{{ __('notice::notice.new') }}</span>@endif
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($notices->lastPage() > 1)
        <div class="mt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-sm text-gray-600">{{ __('notice::notice.page.page_of', ['page' => $notices->currentPage(), 'last' => $notices->lastPage()]) }} • {{ __('notice::notice.page.total', ['count' => $notices->total()]) }}</p>
            {{ $notices->onEachSide(1)->links('theme::partials.diu.pagination') }}
        </div>
    @endif
</x-theme::office-frame>
