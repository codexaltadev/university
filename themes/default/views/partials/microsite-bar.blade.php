{{-- Microsite bar (UI/UX Part B §4.2, §4.3): whose site this is. On desktop the header shows it beside the logo; below desktop this strip does --}}
@if ($identity = $context->identity())
    <div class="microsite-bar">
        <div class="container microsite-bar__inner">
            <a href="{{ $identity['url'] ?? url()->current() }}" class="microsite-bar__name">{{ $identity['name'] }}</a>
            @if ($identity['parent'])
                <a href="{{ $identity['parent']['url'] ?? url('/') }}" class="microsite-bar__parent">{{ $identity['parent']['name'] }}</a>
            @endif
        </div>
    </div>
@endif
