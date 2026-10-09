{{-- Microsite bar (UI/UX Part B §4.2): whose site this is --}}
@if ($context->isMicrosite())
    <div class="microsite-bar">
        <div class="container">
            <a href="{{ url('/') }}">{{ settings('general.site_name') }}</a>
            <span aria-hidden="true">›</span>
            <strong>{{ $context->entity->name ?? '' }}</strong>
        </div>
    </div>
@endif
