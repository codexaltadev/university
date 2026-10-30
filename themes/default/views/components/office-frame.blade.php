{{--
    DIU's office frame (DIUFronend registrar-office/layout.js) around a page of an office's site: the office hero, "Browse by
    Section" — Overview and the office's menu, the page shown marked — with the office's Quick Links, the page beside it.
    Props: office (OfficePage), current (the page's address), crumbs (list of [label, url] after the office), title (the tab's)
--}}
@props(['office', 'current', 'crumbs' => [], 'title' => null])
<x-theme::layout :title="$title ?? $office->office->name">
    @include('theme::partials.diu.hero', [
        'title' => $office->office->name,
        'subtitle' => e($office->subtitle()),
        'image' => $office->image()?->url('large') ?? theme_image('page_hero_image', 'images/page-hero.webp'),
        'breadcrumbs' => [['label' => __('theme::theme.home'), 'url' => url('/')], ['label' => $office->office->name, 'url' => $office->url()], ...$crumbs],
    ])

    <div class="min-h-screen bg-white" x-data="{ more: false }">
        <div class="container mx-auto px-6 py-12">
            <div class="flex flex-col lg:flex-row gap-12">
                @include('theme::partials.diu.browse-sidebar', [
                    'items' => $office->sidebar($current),
                    'quickLinks' => $office->quickLinks(),
                    'width' => 'lg:w-[300px] 2xl:w-[320px]',
                ])
                <div class="flex-1 min-w-0">{{ $slot }}</div>
            </div>
        </div>
    </div>
</x-theme::layout>
