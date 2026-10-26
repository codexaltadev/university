{{--
    /newsletter/issues/{id}: hero with the issue's subject; its date, its text in DIU's article look, the link it came from; the
    sign-up below. Contract: the module's (issue), $page (NewsletterPage)
--}}
<x-theme::layout :title="$issue->subject">
    @if ($i = $page->issue())
        @include('theme::partials.diu.hero', [
            'title' => $i['subject'],
            'subtitle' => null,
            'image' => $page->image() ?? theme_image('page_hero_image', 'images/page-hero.jpg'),
            'breadcrumbs' => $page->breadcrumbs($i['subject']),
        ])

        <div class="bg-white">
            <article class="max-w-4xl mx-auto px-4 sm:px-6 py-12">
                <p class="inline-flex items-center gap-1.5 text-gray-500 mb-6"><x-theme::icon name="calendar" class="size-4" /><time datetime="{{ $i['iso'] }}">{{ $i['date'] }}</time></p>
                @include('theme::partials.page.body', ['body' => $i['body']])
                <div class="flex flex-wrap gap-3 mt-10 pt-6 border-t border-gray-100">
                    @if ($i['source'])<x-theme::button :href="$i['source']['url']" variant="primary" rounded="full" padding="px-6 py-2.5" size="text-base">{{ $i['source']['label'] }}</x-theme::button>@endif
                    <x-theme::button :href="$page->listingUrl()" variant="outlineSecondary" rounded="full" padding="px-5 py-2.5" size="text-base"><x-theme::icon name="arrow-left" class="w-4 h-4" /> {{ __('newsletter::newsletter.page.all') }}</x-theme::button>
                </div>
            </article>
            <section class="bg-secondary">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 py-10 text-white">
                    <h2 class="text-2xl font-bold">{{ __('newsletter::newsletter.page.subscribe') }}</h2>
                    <p class="mt-2 mb-6 text-white/80">{{ $page->intro() }}</p>
                    <livewire:newsletter.subscribe source="page" />
                </div>
            </section>
        </div>
    @endif
</x-theme::layout>
