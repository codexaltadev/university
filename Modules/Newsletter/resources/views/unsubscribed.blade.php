{{-- After one-click unsubscribe (FR-NLT-03): done, and an optional reason. Contract: $subscriber, $saved --}}
<x-theme::layout :title="__('newsletter::newsletter.title')">
    <div class="container" style="padding-block:var(--space-10, 40px);max-width:720px">
        <h1>{{ __('newsletter::newsletter.unsubscribe.title') }}</h1>
        <p role="status">{{ __('newsletter::newsletter.unsubscribe.done') }}</p>
        @if ($saved)
            <p>{{ __('newsletter::newsletter.unsubscribe.thanks') }}</p>
        @else
            <form method="post" action="{{ route('newsletter.unsubscribe.reason', ['subscriber' => $subscriber->token]) }}" style="display:grid;gap:var(--space-3, 12px);margin-top:var(--space-6, 24px)">
                @csrf
                <label for="reason">{{ __('newsletter::newsletter.unsubscribe.why') }}</label>
                <textarea id="reason" name="reason" rows="3" maxlength="255" style="padding:var(--space-3, 12px);border:1px solid var(--color-border);border-radius:var(--radius-sm, 4px)"></textarea>
                <div><button class="btn" type="submit">{{ __('newsletter::newsletter.unsubscribe.send') }}</button></div>
            </form>
        @endif
        <p style="margin-top:var(--space-6, 24px)"><a href="{{ url('/') }}">← {{ __('newsletter::newsletter.home') }}</a></p>
    </div>
</x-theme::layout>
