{{-- Logo; on a microsite also its name and parent microsite (UI/UX Part B §4.2) --}}
<div class="site-brand">
    <a href="{{ url('/') }}" class="site-brand__logo"><x-platform::logo variant="light" class="site-logo" /></a>
    @if ($identity = $context->identity())
        <span class="site-brand__divider" aria-hidden="true"></span>
        <span class="site-brand__entity">
            <a href="{{ $identity['url'] ?? url()->current() }}" class="site-brand__name">{{ $identity['name'] }}</a>
            @if ($identity['parent'])
                <a href="{{ $identity['parent']['url'] ?? url('/') }}" class="site-brand__parent">{{ $identity['parent']['name'] }}<x-theme::icon name="chevron-right" width="14" height="14" /></a>
            @endif
        </span>
    @endif
</div>
