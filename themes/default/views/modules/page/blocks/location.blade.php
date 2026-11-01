{{--
    Location block as DIU's /location (DIUFronend location/page): a white card with the university's campus — name,
    address, phones (help line, telephone, fax), emails, map link — beside the Google map. From Settings › Contact.
    Contract: $campus (LocationBlock::campus) or null
--}}
@if ($campus ?? null)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8 not-prose">
        <div class="bg-white rounded-lg shadow-md p-6 lg:p-8">
            <h2 class="text-xl lg:text-2xl font-bold text-gray-900 mb-4">{{ $campus['name'] }}</h2>
            <div class="space-y-4">
                @if ($campus['address'])
                    <div class="flex items-start gap-3">
                        <x-theme::icon name="map-pin" class="w-5 h-5 text-primary mt-1 shrink-0" />
                        <p class="text-gray-700 whitespace-pre-line">{{ $campus['address'] }}</p>
                    </div>
                @endif
                @foreach ($campus['phones'] as $phone)
                    <div class="flex items-start gap-3">
                        <x-theme::icon :name="$phone['icon']" class="w-5 h-5 text-primary mt-1 shrink-0" />
                        @if ($phone['tel'] && $phone['icon'] !== 'printer')
                            <a href="tel:{{ $phone['tel'] }}" class="text-gray-700 font-medium hover:text-primary">{{ $phone['text'] }}</a>
                        @else
                            <p class="text-gray-700 font-medium">{{ $phone['text'] }}</p>
                        @endif
                    </div>
                @endforeach
                @foreach ($campus['emails'] as $email)
                    <div class="flex items-start gap-3">
                        <x-theme::icon name="mail" class="w-5 h-5 text-primary mt-1 shrink-0" />
                        <a href="mailto:{{ $email }}" class="text-primary hover:underline [overflow-wrap:anywhere]">{{ $email }}</a>
                    </div>
                @endforeach
                @if ($campus['map_link'])
                    <div class="flex items-start gap-3 pt-4 border-t border-gray-200">
                        <x-theme::icon name="map" class="w-5 h-5 text-primary mt-1 shrink-0" />
                        <div>
                            <p class="text-gray-700 font-medium mb-2">{{ __('page::page.map_location') }}</p>
                            <a href="{{ $campus['map_link'] }}" target="_blank" rel="noopener" class="text-primary hover:underline break-all text-sm">{{ $campus['map_link'] }}</a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        @if ($campus['map'])
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <iframe src="{{ $campus['map'] }}" title="{{ $campus['name'] }}" width="100%" height="420" class="block border-0 rounded-xl" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
            </div>
        @endif
    </div>
@endif
