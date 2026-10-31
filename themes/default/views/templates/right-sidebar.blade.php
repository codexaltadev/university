{{--
    Page template "right-sidebar" (FR-PAGE-04): as the left sidebar one, the blocks first and the sidebar ("Browse by Section",
    Quick Links) on the right; on a phone the sidebar comes under the blocks. Contract: $page (title, excerpt, quick_links), $body
--}}
<x-theme::layout :title="$page->title">
    <div class="min-h-screen bg-white text-slate-900">
        @include('theme::partials.page.hero', ['page' => $page, 'subtitle' => filled($page->excerpt) ? e($page->excerpt) : null])
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col lg:flex-row gap-8">
            <article class="page--right-sidebar flex-1 min-w-0">
                @include('theme::partials.page.blocks')
            </article>
            <aside class="w-full lg:w-[360px] 2xl:w-[400px] shrink-0">
                @include('theme::partials.page.sidebar', ['page' => $page])
            </aside>
        </div>
    </div>
</x-theme::layout>
