{{--
    /admission/contact as DIU's /admission-contact: hero; the office (name, address, helpline, email), Connect With Us and
    Apply Online beside its map; each contact group's people as DIU's cards (photo, name, designation, cell, ext, email).
    Texts and links from Settings › Admission. Contract: $page (AdmissionPage)
--}}
<x-theme::layout :title="__('admission::admission.contact_page.title')">
    @include('theme::partials.diu.hero', [
        'title' => __('admission::admission.contact_page.title'),
        'subtitle' => null,
        'image' => theme_image('page_hero_image', 'images/page-hero.webp'),
        'breadcrumbs' => $page->breadcrumbs(__('admission::admission.contact_page.title')),
    ])

    <div class="bg-white">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
            @if ($office = $page->office())
            <div class="grid gap-10 lg:grid-cols-2 items-start">
                <div>
                    @if ($office['name'])<h2 class="text-3xl md:text-[34px] font-semibold text-neutral-900">{{ $office['name'] }}</h2>@endif
                    <div class="mt-3 space-y-2 text-lg text-slate-600">
                        @if ($office['address'])<p>{{ $office['address'] }}</p>@endif
                        @if ($office['helpline'])<p>{{ __('admission::admission.contact_page.helpline') }}: <a href="tel:{{ $office['tel'] }}" class="text-secondary underline">{{ $office['helpline'] }}</a></p>@endif
                        @if ($office['email'])<p>{{ __('admission::admission.contact_page.email') }}: <a href="mailto:{{ $office['email'] }}" class="text-secondary underline">{{ $office['email'] }}</a></p>@endif
                    </div>
                    @if (($social = $page->social()) !== [])
                        <p class="mt-6 text-lg font-medium text-neutral-900">{{ __('admission::admission.contact_page.connect') }}</p>
                        <div class="mt-3 flex flex-wrap items-center gap-5 text-neutral-900">
                            @foreach ($social as $link)
                                <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" class="hover:text-secondary"><x-theme::icon :name="$link['platform'] ?? 'external'" width="24" height="24" /><span class="sr-only">{{ $link['label'] ?? ucfirst($link['platform'] ?? '') }}</span></a>
                            @endforeach
                        </div>
                    @endif
                    @if ($apply = $page->applyUrl())
                        <a href="{{ $apply }}" target="_blank" rel="noopener" class="mt-8 inline-flex rounded-md bg-secondary px-6 py-2.5 text-lg font-medium text-white hover:bg-primary hover:no-underline">{{ __('admission::admission.contact_page.apply') }}</a>
                    @endif
                </div>
                @if ($office['map'])
                    <iframe src="{{ $office['map'] }}" title="{{ $office['name'] }}" class="w-full h-[420px] rounded-xl border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                @endif
            </div>
            @endif

            @foreach ($page->contacts() as $group)
                <h2 class="mt-16 mb-8 text-center text-3xl md:text-[34px] font-semibold text-neutral-900">{{ $group['name'] }}</h2>
                <ul class="grid grid-cols-1 gap-8 md:grid-cols-2" role="list">
                    @foreach ($group['people'] as $person)
                        <li class="min-w-0 flex items-center gap-4 sm:gap-6 rounded-xl bg-slate-50 p-4 sm:p-6 shadow-sm">
                            @if ($person['photo'])
                                <img src="{{ $person['photo'] }}" alt="{{ $person['name'] }}" loading="lazy" class="w-16 h-16 sm:w-24 sm:h-24 shrink-0 rounded-md object-cover object-top">
                            @else
                                <span class="grid place-items-center w-16 h-16 sm:w-24 sm:h-24 shrink-0 rounded-md bg-blue-50 text-primary"><x-theme::icon name="user" class="size-10" /></span>
                            @endif
                            <div class="min-w-0 space-y-1 text-base sm:text-lg text-slate-600 wrap-break-word">
                                <p class="text-xl font-medium text-neutral-900">{{ $person['name'] }}</p>
                                @if ($person['designation'])<p>{{ $person['designation'] }}</p>@endif
                                @if ($person['phone'])<p>{{ __('admission::admission.contact_page.cell') }}: <a href="tel:{{ $person['tel'] }}" class="text-secondary underline">{{ $person['phone'] }}</a></p>@endif
                                @if ($person['ext'])<p>{{ __('admission::admission.ext') }}: {{ $person['ext'] }}</p>@endif
                                @if ($person['email'])<p class="break-all"><a href="mailto:{{ $person['email'] }}" class="text-secondary underline">{{ $person['email'] }}</a></p>@endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </div>
    </div>
</x-theme::layout>
