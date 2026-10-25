{{-- Page template "full-width" (FR-PAGE-04): DIU's hero, then the blocks edge to edge. Contract: $page (title), $body (rendered blocks, sanitised by the kernel) --}}
<x-theme::layout :title="$page->title">
    @include('theme::partials.page.hero', ['page' => $page])
    <article class="page--full-width py-8">
        @include('theme::partials.page.blocks')
    </article>
</x-theme::layout>
