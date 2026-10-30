{{--
    /newsletter: hero with the title and line; the sign-up in a navy card (the theme's newsletter form, as in DIU's footer) beside
    the past issues as cards (date, subject, a few words, Read) with DIU's page numbers. Title, line, picture and whether the
    issues show from Settings › Newsletter. Contract: the module's (intro, issues), $page (NewsletterPage)
--}}
<x-theme::layout :title="$page->title()">
    @include('theme::partials.diu.hero', [
        'title' => $page->title(),
        'subtitle' => e($page->intro()),
        'image' => $page->image() ?? theme_image('page_hero_image', 'images/page-hero.webp'),
        'breadcrumbs' => $page->breadcrumbs(),
    ])

    <div class="bg-gray-50">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 grid gap-10 lg:grid-cols-3 items-start">
            <section class="lg:sticky lg:top-28 rounded-2xl bg-secondary p-6 md:p-8 text-white shadow-xl" aria-labelledby="newsletter-subscribe">
                <span class="grid place-items-center size-12 rounded-full bg-white/10 mb-4"><x-theme::icon name="mail" class="size-6" /></span>
                <h2 id="newsletter-subscribe" class="text-2xl font-bold">{{ __('newsletter::newsletter.page.subscribe') }}</h2>
                <p class="mt-2 mb-6 text-white/80">{{ $page->intro() }}</p>
                <livewire:newsletter.subscribe source="page" />
            </section>

            @if ($page->paginator())
                <section class="lg:col-span-2" aria-labelledby="newsletter-issues">
                    <h2 id="newsletter-issues" class="text-2xl md:text-[34px] font-semibold text-neutral-800 mb-6">{{ __('newsletter::newsletter.page.issues') }}</h2>
                    @if (($issues = $page->issues()) === [])
                        <p class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500">{{ __('newsletter::newsletter.page.none') }}</p>
                    @else
                        <div class="grid gap-5 sm:grid-cols-2">
                            @foreach ($issues as $issue)
                                <a href="{{ $issue['url'] }}" class="group flex flex-col rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md hover:no-underline">
                                    <span class="inline-flex items-center gap-1.5 text-sm text-gray-500"><x-theme::icon name="calendar" class="size-4" /><time datetime="{{ $issue['iso'] }}">{{ $issue['date'] }}</time></span>
                                    <span class="mt-3 text-lg font-semibold text-neutral-900 group-hover:text-primary line-clamp-2">{{ $issue['subject'] }}</span>
                                    <span class="mt-2 text-sm text-gray-600 line-clamp-3">{{ $issue['text'] }}</span>
                                    <span class="flex-1"></span>
                                    <span class="mt-4 inline-flex items-center gap-1 font-medium text-secondary">{{ __('newsletter::newsletter.page.read') }} <x-theme::icon name="arrow-up-right" class="size-4 transition-transform group-hover:rotate-45" /></span>
                                </a>
                            @endforeach
                        </div>
                        {{ $page->paginator()->links('theme::partials.diu.pagination') }}
                    @endif
                </section>
            @endif
        </div>
    </div>
</x-theme::layout>
