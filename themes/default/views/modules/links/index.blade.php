{{--
    /links (Important Links) in DIU's look: hero; each group with its links as tiles (icon, title, a line, an
    arrow for a new tab) or a list. Contract: $groups (list of ['group' => ?Term, 'links' => Link list]), $layout (tiles / list)
--}}
<x-theme::layout :title="__('links::links.plural')">
    @include('theme::partials.diu.hero', [
        'title' => __('links::links.plural'),
        'subtitle' => null,
        'image' => theme_image('page_hero_image', 'images/page-hero.webp'),
        'breadcrumbs' => [['label' => __('theme::theme.home'), 'url' => url('/')], ['label' => __('links::links.plural'), 'url' => request()->url()]],
    ])

    <div class="min-h-screen bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-12">
            @forelse ($groups as $group)
                <section>
                    @if ($group['group'] || count($groups) > 1)
                        <div class="border-b border-gray-200 pb-4 mb-6"><h2 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $group['group']?->name ?? __('links::links.other') }}</h2></div>
                    @endif
                    <div @class(['grid gap-4', 'sm:grid-cols-2 lg:grid-cols-4' => $layout === 'tiles', 'grid-cols-1' => $layout !== 'tiles'])>
                        @foreach ($group['links'] as $link)
                            <a href="{{ $link->href() }}" @if ($link->open_in_new_tab) target="_blank" rel="noopener" @endif
                               @class(['group bg-white border border-gray-200 rounded-xl hover:border-gray-900 hover:shadow-md transition-all hover:no-underline', 'p-6 flex flex-col items-start gap-3 h-full' => $layout === 'tiles', 'px-5 py-4 flex items-center gap-4' => $layout !== 'tiles'])>
                                @if ($icon = $link->firstMedia('icon'))
                                    <img src="{{ $icon->url('thumb') ?? $icon->url() }}" alt="" loading="lazy" @class(['object-contain', 'w-12 h-12' => $layout === 'tiles', 'w-8 h-8' => $layout !== 'tiles'])>
                                @else
                                    <span class="w-10 h-10 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0"><x-theme::icon name="link" class="w-5 h-5" /></span>
                                @endif
                                <span class="flex-1 min-w-0">
                                    <span class="block font-semibold text-gray-900 group-hover:text-primary">{{ $link->title }}</span>
                                    @if ($link->description)<span class="block text-sm text-gray-500 mt-1">{{ $link->description }}</span>@endif
                                </span>
                                @if ($link->open_in_new_tab)<x-theme::icon name="arrow-up-right" class="w-4 h-4 text-secondary shrink-0" /><span class="sr-only">{{ __('links::links.new_tab') }}</span>@endif
                            </a>
                        @endforeach
                    </div>
                </section>
            @empty
                <p class="text-center text-gray-500 py-12">{{ __('links::links.none') }}</p>
            @endforelse
        </div>
    </div>
</x-theme::layout>
