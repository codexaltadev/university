{{-- Microsite trail (UI/UX Part B §3): Home › Faculty › Department › Program / Lab; nothing on the main site --}}
@if ($context->isMicrosite())
    <div class="breadcrumb-bar">
        <div class="container"><x-platform::breadcrumb :context="$context" /></div>
    </div>
@endif
