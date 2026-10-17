{{--
    DIU EssentialCampusServices with the microsite's labs ("Cutting-Edge Research & Labs"); the
    service cards when it has none. Contract: $section (title, subheading, content: heading, subheading, cards), $items (Lab)
--}}
@include('theme::partials.diu.service-cards', [
    'heading' => $section->title ?: $section->content['heading'],
    'subheading' => $section->subheading ?: $section->content['subheading'],
    'cards' => collect($items ?? [])->isNotEmpty()
        ? collect($items)->map(fn ($lab) => [
            'image' => ($image = $lab->firstMedia('cover') ?? $lab->firstMedia('logo')) ? ['url' => $image->url('large') ?? $image->url()] : null,
            'title' => $lab->name,
            'text' => $lab->location,
            'url' => url((string) $lab->url()),
        ])->all()
        : $section->content['cards'],
])
