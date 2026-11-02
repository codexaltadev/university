{{-- Page template "with-sidebar" = left sidebar (FR-PAGE-04): the page's sidebar menu, the blocks beside it. Contract: $page, $body --}}
<x-theme::layout :title="$page->title">
    <x-theme::page-hero :title="$page->title" :text="$page->excerpt" :crumbs="[['label' => $page->title]]" />
    <div class="container page-body with-aside with-aside--left">
        <aside class="aside-stack">@include('theme::partials.page.sidebar')</aside>
        <article class="min-w-0 page--with-sidebar">{!! $body !!}</article>
    </div>
</x-theme::layout>
