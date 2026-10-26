{{-- /newsletter/issues/{id}: one past issue. Contract: $issue (Campaign), $page (Support\NewsletterPage) --}}
<x-theme::layout :title="$issue->subject">
    <div class="container" style="padding-block:var(--space-10, 40px);max-width:820px">
        @if ($i = $page->issue())
            <p><a href="{{ $page->listingUrl() }}">← {{ $page->title() }}</a></p>
            <h1>{{ $i['subject'] }}</h1>
            <p><time datetime="{{ $i['iso'] }}">{{ $i['date'] }}</time></p>
            <div class="prose">{!! $i['body'] !!}</div>
            @if ($i['source'])<p><a href="{{ $i['source']['url'] }}">{{ $i['source']['label'] }}</a></p>@endif
        @endif
    </div>
</x-theme::layout>
