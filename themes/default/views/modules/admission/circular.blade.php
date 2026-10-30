{{--
    /admission/circulars/{slug}: hero with the circular's name; its notice (dates with their status), its text in DIU's article
    look, Apply Now while open and its PDF. Contract: $circular; $page (AdmissionPage)
--}}
<x-theme::layout :title="$circular->title">
    @if ($c = $page->circular())
        @include('theme::partials.diu.hero', [
            'title' => $c['title'],
            'subtitle' => null,
            'image' => theme_image('page_hero_image', 'images/page-hero.webp'),
            'breadcrumbs' => $page->breadcrumbs($c['title'], __('admission::admission.circular.plural'), $page->circularsUrl()),
        ])

        <div class="bg-white">
            <article class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                @include('theme::partials.admission.circular', ['circular' => $c, 'link' => false])
                @if ($c['body'])
                    <div class="mt-8">@include('theme::partials.page.body', ['body' => $c['body']])</div>
                @endif
                <div class="flex flex-wrap gap-3 mt-10 pt-6 border-t border-gray-100">
                    @if ($c['apply'])<x-theme::button :href="$c['apply']" target="_blank" variant="primary" rounded="full" padding="px-6 py-2.5" size="text-base">{{ __('admission::admission.apply_now') }}</x-theme::button>@endif
                    @if ($c['pdf'])<x-theme::button :href="$c['pdf']" target="_blank" variant="outlineSecondary" rounded="full" padding="px-5 py-2.5" size="text-base"><x-theme::icon name="download" class="w-4 h-4" /> {{ __('admission::admission.fields.pdf') }}</x-theme::button>@endif
                    <x-theme::button :href="$page->circularsUrl()" variant="outlineSecondary" rounded="full" padding="px-5 py-2.5" size="text-base"><x-theme::icon name="arrow-left" class="w-4 h-4" /> {{ __('admission::admission.circular.plural') }}</x-theme::button>
                </div>
            </article>
        </div>
    @endif
</x-theme::layout>
