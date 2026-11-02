{{-- Page template "full-width" (FR-PAGE-04): page hero, the blocks across the container. Contract: $page, $body --}}
<x-theme::layout :title="$page->title">
    <x-theme::page-hero :title="$page->title" :text="$page->excerpt" :crumbs="[['label' => $page->title]]" />
    <div class="container page-body page--full-width">{!! $body !!}</div>
</x-theme::layout>
