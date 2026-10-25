{{-- Page template "default" (FR-PAGE-04), DIU's article page: hero, then the text on grey. Contract: $page (title), $body (rendered blocks, sanitised by the kernel) --}}
<x-theme::layout :title="$page->title">
    <div class="min-h-screen bg-gray-50">
        @include('theme::partials.page.hero', ['page' => $page])
        <div class="container mx-auto px-4 py-8 mt-10">
            <article class="page--default overflow-hidden">
                <div class="pl-4">@include('theme::partials.page.blocks')</div>
            </article>
        </div>
    </div>
</x-theme::layout>
