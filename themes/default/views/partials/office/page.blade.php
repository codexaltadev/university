{{--
    A page of an office's site in the office's frame, as DIU's /registrar-office/{slug} (ArticleSection): the page title, its
    text in a white card (a module's block — the Academic Calendar … — in its own look), then the pages under it as cards
    (Employee Service → Recruitment …). Contract: $page (Page), $office (OfficePage), $body (rendered blocks)
--}}
<x-theme::office-frame :office="$office" :current="$page->url()" :title="$page->title"
    :crumbs="[...array_map(fn ($parent) => ['label' => $parent->title, 'url' => url($parent->url())], $page->ancestors()), ['label' => $page->title, 'url' => url($page->url())]]">
    <article class="page--office space-y-8">
        <h2 class="lg:leading-16 text-[24px] md:text-3xl lg:text-5xl font-bold text-neutral-800 mb-4">{{ $page->title }}</h2>

        @if (trim((string) $body) !== '')
            <div class="space-y-6 [&_diu-rich-html]:bg-white [&_diu-rich-html]:rounded-xl [&_diu-rich-html]:shadow [&_diu-rich-html]:p-6 md:[&_diu-rich-html]:p-8">
                @include('theme::partials.page.blocks')
            </div>
        @endif

        @if (($subpages = $office->subpages($page)) !== [])
            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-6" role="list">
                @foreach ($subpages as $sub)
                    <li>
                        <a href="{{ $sub['url'] }}" class="group h-full flex items-start gap-4 bg-white border border-gray-200 rounded-xl p-6 hover:shadow-lg hover:border-primary/40 transition hover:no-underline">
                            <span class="p-3 bg-primary/10 rounded-lg text-primary shrink-0"><x-theme::icon name="file-text" class="w-6 h-6" /></span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-lg font-semibold text-gray-900 group-hover:text-primary">{{ $sub['title'] }}</span>
                                @if ($sub['excerpt'])<span class="block mt-1 text-sm text-gray-500 leading-relaxed">{{ $sub['excerpt'] }}</span>@endif
                            </span>
                            <x-theme::icon name="arrow-right" class="w-5 h-5 text-gray-400 group-hover:text-primary shrink-0 mt-1" />
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </article>
</x-theme::office-frame>
