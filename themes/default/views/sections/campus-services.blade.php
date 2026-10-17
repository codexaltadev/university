{{-- DIU EssentialCampusServices: service cards. Contract: $section (title, subheading, content: heading, subheading, cards) --}}
@include('theme::partials.diu.service-cards', [
    'heading' => $section->title ?: $section->content['heading'],
    'subheading' => $section->subheading ?: $section->content['subheading'],
    'cards' => $section->content['cards'],
])
