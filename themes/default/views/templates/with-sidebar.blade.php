{{-- Page template "with-sidebar" (FR-PAGE-04): DIU's hero, the text beside the page's sidebar menu. Contract: $page (title), $body --}}
<x-theme::layout :title="$page->title">
    <div class="min-h-screen bg-gray-50">
        @include('theme::partials.page.hero', ['page' => $page])
        <div class="container mx-auto px-4 py-8 mt-10 flex flex-col lg:flex-row gap-12">
            <aside class="w-full lg:w-[320px] xl:w-[400px] shrink-0 order-last lg:order-first">
                <div class="sticky top-32 space-y-6">
                    <x-platform::slot name="sidebar" />
                    <x-platform::menu location="sidebar" :depth="2" />
                </div>
            </aside>
            <article class="page--with-sidebar flex-1 min-w-0">
                @include('theme::partials.page.body')
            </article>
        </div>
    </div>
</x-theme::layout>
