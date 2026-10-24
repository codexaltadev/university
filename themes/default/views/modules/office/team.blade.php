{{-- DIU /office/{slug}/team: the office hero, then every member, head first. Contract: $entity (Office), $page (OfficePage) --}}
<x-theme::layout :title="__('office::office.page.team_members').' — '.$entity->name">
    @include('theme::partials.diu.hero', [
        'title' => __('office::office.page.team_members'),
        'subtitle' => e(__('office::office.page.site_suffix', ['name' => $entity->name, 'site' => settings('general.site_name')])),
        'image' => $page->image()?->url('large') ?? theme_image('page_hero_image', 'images/page-hero.jpg'),
        'breadcrumbs' => [['label' => __('theme::theme.home'), 'url' => url('/')], ['label' => $entity->name, 'url' => $page->url()], ['label' => __('office::office.page.team'), 'url' => $page->url('team')]],
    ])

    <div class="min-h-screen bg-gray-50/40">
        <div class="container mx-auto px-6 py-12">
            <section class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 sm:p-8" aria-labelledby="office-team">
                <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
                    <div>
                        <h2 id="office-team" class="text-2xl sm:text-3xl font-bold text-secondary">{{ __('office::office.page.team_members') }}</h2>
                        <p class="text-gray-500 mt-1">{{ __('office::office.page.team_members_text') }}</p>
                    </div>
                    <a href="{{ $page->url() }}" class="text-primary font-semibold hover:underline inline-flex items-center gap-1">
                        <x-theme::icon name="arrow-left" class="w-4 h-4" /> {{ $entity->name }}
                    </a>
                </div>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach ($page->team() as $person)
                        @include('theme::partials.office.member', ['person' => $person])
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-theme::layout>
