{{--
    A teacher in DIU's DeanCard look (components/UI/DeanCard), smaller: photo (or initials on DIU's navy → blue), name,
    designation, department, contacts, and "View Profile" — the profile on the faculty website, in a new tab.
    Contract: $card (FacultyMembersPage::card())
--}}
<article class="faculty-member-card group flex flex-col bg-neutral-100 p-3 md:p-5 rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300">
    <div class="relative pb-4">
        @if ($card['photo'])
            <img src="{{ $card['photo'] }}" alt="{{ $card['name'] }}" loading="lazy" class="rounded-xl w-full aspect-[4/5] object-cover object-top">
        @else
            <div class="rounded-xl w-full aspect-[4/5] bg-gradient-to-br from-secondary to-primary flex items-center justify-center" aria-hidden="true">
                <span class="text-5xl font-bold tracking-wide text-white/90">{{ $card['initials'] }}</span>
            </div>
        @endif
        @if ($card['on_leave'])
            <div class="absolute top-4 left-0 bg-white py-1.5 px-2.5 z-10"><span class="text-sm text-neutral-800 font-semibold">{{ __('facultymember::facultymember.employment.on_leave') }}</span></div>
        @endif
    </div>

    <h3 class="text-xl font-semibold text-neutral-800 leading-tight">{{ $card['name'] }}</h3>
    @if ($card['designation'])<p class="mt-1 text-base font-medium text-secondary">{{ $card['designation'] }}</p>@endif
    @if ($card['department'])
        <p class="mt-1 text-sm text-neutral-500 line-clamp-2">@if ($card['department_url'])<a href="{{ $card['department_url'] }}" class="text-inherit hover:text-primary">{{ $card['department'] }}</a>@else{{ $card['department'] }}@endif</p>
    @endif

    <ul class="mt-3 mb-4 space-y-1.5 text-sm text-neutral-600">
        @if ($card['email'])<li class="flex items-center gap-2 min-w-0"><x-theme::icon name="mail" class="w-4 h-4 text-gray-400 shrink-0" /><span class="truncate [&_a]:text-primary">{{ $card['email'] }}</span></li>@endif
        @if ($card['phone'])<li class="flex items-center gap-2"><x-theme::icon name="phone" class="w-4 h-4 text-gray-400 shrink-0" /><a href="tel:{{ $card['tel'] }}" class="text-neutral-700 hover:text-primary">{{ $card['phone'] }}</a>@if ($card['extension'])<span class="text-neutral-500">· {{ __('facultymember::facultymember.ext') }} {{ $card['extension'] }}</span>@endif</li>@endif
        @if ($card['room'])<li class="flex items-center gap-2"><x-theme::icon name="map-pin" class="w-4 h-4 text-gray-400 shrink-0" />{{ $card['room'] }}</li>@endif
    </ul>

    @if ($card['url'])
        <div class="mt-auto">
            <div class="bg-gray-200 mb-3 w-full h-[0.5px]"></div>
            <a href="{{ $card['url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-secondary font-medium text-lg hover:text-sky-700 hover:no-underline transition-colors">
                {{ __('facultymember::facultymember.page.view_profile') }}
                <x-theme::icon name="arrow-up-right" class="w-4 h-4 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
            </a>
        </div>
    @endif
</article>
