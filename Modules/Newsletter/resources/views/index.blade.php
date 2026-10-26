{{-- /newsletter (FR-NLT-01): the sign-up form with the lists to choose, the past issues. Contract: $intro, $issues (paginator or null), $page (Support\NewsletterPage) --}}
<x-theme::layout :title="__('newsletter::newsletter.title')">
    <div class="container" style="padding-block:var(--space-10, 40px);max-width:720px">
        <h1>{{ __('newsletter::newsletter.title') }}</h1>
        <p>{{ $intro ?: __('newsletter::newsletter.footer.default_line') }}</p>
        <livewire:newsletter.subscribe source="page" />
        @if (($issues = $page->issues()) !== [])
            <h2 style="margin-top:var(--space-10, 40px)">{{ __('newsletter::newsletter.page.issues') }}</h2>
            <ul>
                @foreach ($issues as $i)
                    <li><a href="{{ $i['url'] }}">{{ $i['subject'] }}</a> — <time datetime="{{ $i['iso'] }}">{{ $i['date'] }}</time></li>
                @endforeach
            </ul>
            {{ $page->paginator()->links() }}
        @endif
    </div>
</x-theme::layout>
