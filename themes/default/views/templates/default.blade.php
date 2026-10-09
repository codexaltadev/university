{{-- Page template "default" (FR-PAGE-04). Contract: $page (title), $body (rendered blocks, sanitised by the kernel) --}}
<x-theme::layout :title="$page->title">
    <article @class(['page', 'container' => 'default' !== 'full-width', 'page--default'])>
        @if ('default' !== 'landing')
            <h1 class="page__title">{{ $page->title }}</h1>
        @endif
        <div class="prose">{!! $body !!}</div>
    </article>
</x-theme::layout>
