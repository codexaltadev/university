{{--
    A graduate as DIU's AlumniList shows one (components/Academics/Department/Moduels/AlumniList): a 3:4 photo that grows on
    hover (or initials on DIU's navy → blue), name, designation with a briefcase, organization with a building, the
    department as a grey badge; "Pride" on the photo. The card opens their page. Contract: $card (AlumniPage::card())
--}}
<a href="{{ $card['url'] }}" class="alumni-card group block hover:no-underline">
    <div class="relative aspect-[3/4] bg-gray-100 mb-5 overflow-hidden">
        @if ($card['photo'])
            <img src="{{ $card['photo'] }}" alt="{{ $card['name'] }}" loading="lazy" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
        @else
            <div class="w-full h-full bg-gradient-to-br from-secondary to-primary flex items-center justify-center transition-transform duration-500 group-hover:scale-105" aria-hidden="true">
                <span class="text-6xl font-bold text-white/90">{{ $card['initials'] }}</span>
            </div>
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        @if ($card['pride'])
            <span class="absolute top-4 left-0 bg-white py-1.5 px-2.5 text-sm font-semibold text-neutral-800 flex items-center gap-1.5"><x-theme::icon name="sparkles" class="w-4 h-4 text-amber-500" />{{ __('alumni::alumni.pride_badge') }}</span>
        @endif
    </div>
    <div class="space-y-3">
        <h3 class="text-lg font-bold text-gray-900 leading-tight group-hover:text-gray-600 transition-colors">{{ $card['name'] }}</h3>
        @if ($card['designation'])
            <p class="flex items-start gap-2 text-sm text-gray-700 leading-relaxed"><x-theme::icon name="briefcase" class="w-4 h-4 text-gray-400 shrink-0 mt-0.5" />{{ $card['designation'] }}</p>
        @endif
        @if ($card['organization'])
            <p class="flex items-start gap-2 text-sm text-gray-600 leading-relaxed"><x-theme::icon name="building" class="w-4 h-4 text-gray-400 shrink-0 mt-0.5" />{{ $card['organization'] }}</p>
        @endif
        @if ($card['department'] || $card['batch'])
            <div class="pt-3 border-t border-gray-100 flex flex-wrap gap-2">
                @if ($card['department'])<span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 text-xs font-medium text-gray-700"><x-theme::icon name="graduation-cap" class="w-3.5 h-3.5 text-gray-600" />{{ $card['department'] }}</span>@endif
                @if ($card['batch'])<span class="inline-flex items-center px-2.5 py-1 bg-gray-100 text-xs font-medium text-gray-700">{{ __('alumni::alumni.batch_n', ['batch' => $card['batch']]) }}</span>@endif
            </div>
        @endif
    </div>
</a>
