{{-- /faculties, layout "card": DIU's page whatever the layout (theme::partials.faculty.listing). Contract: $faculties, $options --}}
@include('theme::partials.faculty.listing', ['faculties' => $faculties])
