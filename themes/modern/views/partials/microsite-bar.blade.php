{{-- Microsite bar (UI/UX Part B §4.2): the microsite's name and the place it belongs to --}}
@if ($identity = $context->identity())
    <div class="microsite-bar no-print">
        <div class="container microsite-bar__inner">
            <a href="{{ $identity['url'] ?? url()->current() }}" class="microsite-bar__name">{{ $identity['name'] }}</a>
            @if ($identity['parent']['name'] ?? null)
                <a href="{{ $identity['parent']['url'] ?? url('/') }}" class="microsite-bar__parent">{{ __('theme::theme.part_of', ['name' => $identity['parent']['name']]) }}</a>
            @endif
        </div>
    </div>
@endif
