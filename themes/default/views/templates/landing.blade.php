{{-- Page template "landing" (FR-PAGE-04). Contract: $page (title), $body (rendered blocks, sanitised by the kernel) --}}
<x-theme::layout :title="$page->title">
    <article @class(['page', 'container' => 'landing' !== 'full-width', 'page--landing'])>
        @if ('landing' !== 'landing')
            <h1 class="page__title">{{ $page->title }}</h1>
        @endif
        <div class="prose">{!! $body !!}</div>
    </article>
</x-theme::layout>
