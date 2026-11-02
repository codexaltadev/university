{{-- Page template "landing" (FR-PAGE-04): no page hero — the blocks alone, each across the page (campaigns, admission). Contract: $page, $body --}}
<x-theme::layout :title="$page->title">
    <h1 class="sr-only">{{ $page->title }}</h1>
    <div class="container page-body page--landing">{!! $body !!}</div>
</x-theme::layout>
