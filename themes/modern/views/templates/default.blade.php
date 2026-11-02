{{-- Page template "default" (FR-PAGE-04): page hero, the text in a reading column. Contract: $page (title, excerpt), $body --}}
<x-theme::layout :title="$page->title">
    <x-theme::page-hero :title="$page->title" :text="$page->excerpt" :crumbs="[['label' => $page->title]]" />
    <div class="container page-body">
        <article class="article page--default">{!! $body !!}</article>
    </div>
</x-theme::layout>
