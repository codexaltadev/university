{{-- Page template "with-sidebar" (FR-PAGE-04). Contract: $page (title), $body --}}
<x-theme::layout :title="$page->title">
    <div class="container page-grid">
        <article class="page">
            <h1 class="page__title">{{ $page->title }}</h1>
            <div class="prose">{!! $body !!}</div>
        </article>
        <aside class="page-grid__aside">
            <x-platform::slot name="sidebar" />
            <x-platform::menu location="sidebar" :depth="2" />
        </aside>
    </div>
</x-theme::layout>
