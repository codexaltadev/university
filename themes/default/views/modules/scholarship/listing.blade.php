{{--
    /scholarships as DIU's /scholarship (diu-scholarship): hero; "Browse by Section" and Quick Links on the left (Settings ›
    Scholarships); the text above (DIU's article look), each category's scholarships as folding items, the text below.
    Contract: the module's (groups, categories, selected, scholarships, layout, intro, outro), $page (ScholarshipPage)
--}}
<x-theme::layout :title="$page->title()">
    @include('theme::partials.diu.hero', [
        'title' => $page->title(),
        'subtitle' => $page->text() ? e($page->text()) : null,
        'image' => $page->image() ?? theme_image('page_hero_image', 'images/page-hero.webp'),
        'breadcrumbs' => $page->breadcrumbs(),
    ])

    <div class="bg-white">
        <div class="container mx-auto px-4 sm:px-6 py-12 flex flex-col lg:flex-row gap-12" x-data="{ current: null, more: false, open: null }">
            @if (($side = $page->sideLinks()) !== [] || $page->quickLinks() !== [])
                @include('theme::partials.diu.browse-sidebar', ['items' => $side, 'quickLinks' => $page->quickLinks(), 'width' => 'lg:w-[420px]'])
            @endif
            <div class="flex-1 min-w-0">
                @if ($intro = $page->intro())<div class="mb-10">@include('theme::partials.page.body', ['body' => $intro])</div>@endif

                <div class="space-y-8">
                    @foreach ($page->groups() as $group)
                        <section class="space-y-4">
                            <h2 class="text-xl font-bold text-blue-500 border-b border-gray-200 pb-2">{{ $group['label'] }}</h2>
                            <div class="space-y-3">
                                @foreach ($group['items'] as $item)
                                    @include('theme::partials.scholarship.item', ['item' => $item])
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>

                @if ($outro = $page->outro())<div class="mt-10">@include('theme::partials.page.body', ['body' => $outro])</div>@endif
            </div>
        </div>
    </div>
</x-theme::layout>
