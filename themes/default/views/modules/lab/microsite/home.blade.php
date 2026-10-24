{{--
    DIU lab page (DIUFronend app/(public)/lab-facilities/[department]/[lab]): hero with the department's
    name and the way back (Laboratory Facilities › department › lab), the department's name again with
    Settings › Labs' line, then its labs on the left and this lab's page beside.
    Contract: $entity (Lab), $context, $page (LabPage)
--}}
@include('theme::partials.diu.hero', [
    'title' => $page->department()?->name ?? $entity->name,
    'subtitle' => e($page->heroText()),
    'image' => $page->image()?->url('large') ?? media_url(settings('lab.listing_image'), 'large') ?? theme_asset('images/faculty-banner.jpg'),
    'breadcrumbs' => array_values(array_filter([
        ['label' => __('theme::theme.home'), 'url' => url('/')],
        ['label' => settings('lab.listing_title') ?: __('lab::lab.page.laboratories'), 'url' => url('/labs')],
        $page->department() ? ['label' => $page->department()->short_name ?: $page->department()->name, 'url' => $page->labsUrl()] : null,
        ['label' => $entity->name, 'url' => $entity->url()],
    ])),
])

<main class="min-h-screen bg-white">
    <div class="container mx-auto px-4 py-8">
        @if ($page->department())
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">{{ $page->department()->name }}</h2>
                <p class="text-gray-500">{{ settings('lab.department_text') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <div class="lg:col-span-1">
                @include('theme::partials.lab.sidebar', ['labs' => $page->siblings()])
            </div>
            <div class="lg:col-span-3 min-w-0">
                @include('theme::partials.lab.details', ['lab' => $page])
            </div>
        </div>
    </div>
</main>
