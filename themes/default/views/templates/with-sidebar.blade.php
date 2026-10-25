{{--
    Page template "with-sidebar" (FR-PAGE-04) as DIU's /admission: hero (the page's excerpt under the title), on the left
    "Browse by Section" (the page's sidebar menu) and its Quick Links, the blocks beside it. Contract: $page (title, excerpt, quick_links), $body
--}}
<x-theme::layout :title="$page->title">
    <div class="min-h-screen bg-white text-slate-900">
        @include('theme::partials.page.hero', ['page' => $page, 'subtitle' => filled($page->excerpt) ? e($page->excerpt) : null])
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col lg:flex-row gap-8">
            <aside class="w-full lg:w-[400px] 2xl:w-[450px] shrink-0 order-last lg:order-first">
                <div class="lg:sticky lg:top-32 space-y-6">
                    <x-platform::slot name="sidebar" />
                    <x-platform::menu location="sidebar" :depth="2" view="theme::partials.menus.browse" />
                    @include('theme::partials.diu.quick-links', ['links' => link_list($page->quick_links)])
                </div>
            </aside>
            <article class="page--with-sidebar flex-1 min-w-0">
                @include('theme::partials.page.blocks')
            </article>
        </div>
    </div>
</x-theme::layout>
