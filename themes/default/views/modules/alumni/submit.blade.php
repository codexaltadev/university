{{--
    /alumni/submit — the graduates' form (Livewire "alumni.submit") under DIU's hero, in a white card; the alumni portal beside it.
    Contract: $page (AlumniPage)
--}}
<x-theme::layout :title="__('alumni::alumni.submit.title')">
    @include('theme::partials.diu.hero', [
        'title' => __('alumni::alumni.submit.title'),
        'subtitle' => e(__('alumni::alumni.submit.intro')),
        'image' => $page->image() ?? theme_image('page_hero_image', 'images/page-hero.webp'),
        'breadcrumbs' => $page->breadcrumbs(__('alumni::alumni.submit.title')),
    ])

    <div class="bg-gray-50">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-12">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 sm:p-8">
                <livewire:alumni.submit />
            </div>
            @if ($portal = $page->portal())
                <p class="mt-6 text-center"><a href="{{ $portal['url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-primary font-medium">{{ $portal['label'] }} <x-theme::icon name="arrow-up-right" class="w-4 h-4" /></a></p>
            @endif
        </div>
    </div>
</x-theme::layout>
