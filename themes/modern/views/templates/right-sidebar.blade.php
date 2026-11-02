{{-- Page template "right-sidebar" (FR-PAGE-04): the blocks, the page's sidebar menu on the right. Contract: $page, $body --}}
<x-theme::layout :title="$page->title">
    <x-theme::page-hero :title="$page->title" :text="$page->excerpt" :crumbs="[['label' => $page->title]]" />
    <div class="container page-body with-aside">
        <article class="min-w-0 page--right-sidebar">{!! $body !!}</article>
        <aside class="aside-stack">@include('theme::partials.page.sidebar')</aside>
    </div>
</x-theme::layout>
