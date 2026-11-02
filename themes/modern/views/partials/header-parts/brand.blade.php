{{-- Logo (FR-THEME-06); on a microsite also its name and its parent (UI/UX Part B §4.2) --}}
<div class="site-header__brand">
    <a href="{{ url('/') }}" class="shrink-0"><x-platform::logo variant="light" /></a>
    @if ($identity = $context->identity())
        <a href="{{ $identity['url'] ?? url()->current() }}" class="site-header__entity">
            {{ $identity['name'] }}
            @if ($identity['parent']['name'] ?? null)<small>{{ $identity['parent']['name'] }}</small>@endif
        </a>
    @endif
</div>
