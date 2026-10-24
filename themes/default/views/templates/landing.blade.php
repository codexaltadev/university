{{-- Page template "landing" (FR-PAGE-04): the blocks only, no hero or title (they bring their own). Contract: $page (title), $body (rendered blocks, sanitised by the kernel) --}}
<x-theme::layout :title="$page->title">
    <article class="page page--landing">
        <div class="prose max-w-none">{!! $body !!}</div>
    </article>
</x-theme::layout>
