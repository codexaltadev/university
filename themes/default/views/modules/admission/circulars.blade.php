{{--
    /admission/circulars: hero, then every circular (open first) as DIU's admission notice — its name, Open / Closed, each
    date with Active / Upcoming / Expired. Contract: $circulars; $page (AdmissionPage)
--}}
<x-theme::layout :title="__('admission::admission.circular.plural')">
    @include('theme::partials.diu.hero', [
        'title' => __('admission::admission.circular.plural'),
        'subtitle' => null,
        'image' => theme_image('page_hero_image', 'images/page-hero.jpg'),
        'breadcrumbs' => $page->breadcrumbs(__('admission::admission.circular.plural')),
    ])

    <div class="bg-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-6">
            @forelse ($page->circulars() as $circular)
                @include('theme::partials.admission.circular', ['circular' => $circular, 'link' => true])
            @empty
                <p class="py-16 text-center text-slate-500">{{ __('admission::admission.no_circulars') }}</p>
            @endforelse
        </div>
    </div>
</x-theme::layout>
