{{-- /newsletter (FR-NLT-01): the sign-up form with the lists to choose. Contract: $intro --}}
<x-theme::layout :title="__('newsletter::newsletter.title')">
    <div class="container" style="padding-block:var(--space-10, 40px);max-width:720px">
        <h1>{{ __('newsletter::newsletter.title') }}</h1>
        <p>{{ $intro ?: __('newsletter::newsletter.footer.default_line') }}</p>
        <livewire:newsletter.subscribe source="page" />
    </div>
</x-theme::layout>
