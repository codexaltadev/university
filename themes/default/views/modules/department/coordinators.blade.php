{{--
    /coordination-officers as DIU's /coordination-officer (DIUFronend coordination-officer/page, components/StaffCard):
    hero "Coordination Officer Directory"; per faculty an icon tile and its name, then the officers three to a row —
    photo (initials without one), name with the program in capitals, designation, email, "Cell:", "Ext:".
    Title, text and picture from Settings › Departments. Contract: $page (CoordinatorsPage)
--}}
<x-theme::layout :title="$page->title()">
    @include('theme::partials.diu.hero', [
        'title' => $page->title(),
        'subtitle' => $page->subtitle() ? e($page->subtitle()) : null,
        'image' => $page->image() ?? theme_image('page_hero_image', 'images/page-hero.webp'),
        'breadcrumbs' => [['label' => __('theme::theme.home'), 'url' => url('/')], ['label' => $page->title(), 'url' => $page->url()]],
    ])

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/30 to-indigo-50">
        <div class="container mx-auto px-6 py-16">
            @forelse ($page->groups() as $group)
                <section class="mb-16">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center shrink-0">
                            <x-theme::icon :name="$group['icon']" class="w-6 h-6 text-primary" />
                        </div>
                        <h2 class="text-2xl md:text-3xl font-bold text-secondary">{{ $group['faculty'] }}</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        @foreach ($group['people'] as $person)
                            <div class="flex gap-4 items-center">
                                @if ($person['photo'])
                                    <img src="{{ $person['photo'] }}" alt="{{ $person['name'] }}" loading="lazy" class="w-[120px] h-[140px] rounded-lg object-cover shrink-0">
                                @else
                                    <div class="w-[120px] h-[140px] rounded-lg bg-secondary/10 text-secondary flex items-center justify-center text-3xl font-bold shrink-0" aria-hidden="true">{{ $person['initials'] }}</div>
                                @endif
                                <div class="min-w-0">
                                    <div class="font-semibold text-lg text-gray-900">{{ $person['name'] }}@if ($person['unit'])<span class="uppercase"> ({{ $person['unit'] }})</span>@endif</div>
                                    @if ($person['designation'])<div class="text-base text-gray-600">{{ $person['designation'] }}</div>@endif
                                    @if ($person['email'])<a href="mailto:{{ $person['email'] }}" class="block text-base text-gray-500 hover:text-primary [overflow-wrap:anywhere]">{{ $person['email'] }}</a>@endif
                                    @if ($person['phone'])<a href="tel:{{ $person['tel'] }}" class="block text-base text-gray-500 hover:text-primary">{{ __('department::department.coordinators.cell') }} {{ $person['phone'] }}</a>@endif
                                    @if ($person['extension'])<div class="text-base text-gray-500">{{ __('department::department.coordinators.ext') }} {{ $person['extension'] }}</div>@endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="text-center py-20">
                    <div class="w-32 h-32 bg-gradient-to-r from-slate-200 to-slate-300 rounded-full flex items-center justify-center mx-auto mb-8 shadow-lg">
                        <x-theme::icon name="users" class="h-16 w-16 text-slate-400" />
                    </div>
                    <h3 class="text-3xl font-bold text-slate-800 mb-6">{{ __('department::department.coordinators.none') }}</h3>
                    <p class="text-slate-600 text-lg mb-8 max-w-md mx-auto leading-relaxed">{{ __('department::department.coordinators.none_text') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</x-theme::layout>
