{{--
    Contact of an office (DIU /registrar-office/contact, there typed text): location, phone, email, hours as cards — a tap
    calls or writes — the campus with its address and map, then who to contact for what.
    Contract: $data (intro), $locale; $office (OfficePage or null: the block is on a page outside an office's site)
--}}
@if ($office)
    @foreach ([$office->contact()] as $contact)
        <div class="not-prose space-y-10">
            @if (filled($data['intro'] ?? null))<p class="text-gray-600 md:text-lg leading-relaxed">{{ $data['intro'] }}</p>@endif

            @if ($contact['facts'] !== [])
                <section aria-labelledby="office-get-in-touch">
                    <h3 id="office-get-in-touch" class="text-xl md:text-2xl font-bold text-neutral-800 mb-5">{{ __('office::office.page.get_in_touch') }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        @foreach ($contact['facts'] as $key => $value)
                            <div @class(['group bg-white border border-gray-200 rounded-xl p-6 flex items-start gap-4 hover:shadow-lg hover:border-primary/30 transition', 'sm:col-span-2' => $key === 'location' && count($contact['facts']) % 2 === 1])>
                                <span class="w-12 h-12 shrink-0 rounded-full bg-blue-900 group-hover:bg-blue-800 text-white flex items-center justify-center transition-colors">
                                    <x-theme::icon :name="['location' => 'map-pin', 'phone' => 'phone', 'email' => 'mail', 'extension' => 'phone', 'hours' => 'clock'][$key]" class="w-5 h-5" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('office::office.page.'.$key) }}</p>
                                    @if ($key === 'email')
                                        <a href="mailto:{{ $value }}" class="mt-1 block text-base font-semibold text-gray-900 [overflow-wrap:anywhere] hover:text-primary hover:no-underline">{{ $value }}</a>
                                    @elseif ($key === 'phone')
                                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $value) }}" class="mt-1 block text-lg font-semibold text-gray-900 hover:text-primary hover:no-underline">{{ $value }}</a>
                                    @else
                                        <p class="mt-1 text-lg font-semibold text-gray-900 whitespace-pre-line">{{ $value }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($campus = $contact['campus'])
                @if ($campus['address'] || $campus['map'])
                    <section class="bg-white border border-gray-200 rounded-2xl overflow-hidden" aria-labelledby="office-find-us">
                        <div class="p-6 flex items-start gap-4">
                            <span class="p-3 bg-primary/10 rounded-lg text-primary shrink-0"><x-theme::icon name="building" class="w-6 h-6" /></span>
                            <div class="min-w-0">
                                <h3 id="office-find-us" class="text-xl font-bold text-neutral-800">{{ __('office::office.page.find_us') }}</h3>
                                <p class="mt-1 font-semibold text-gray-800">{{ $campus['name'] }}</p>
                                @if ($campus['address'])<p class="text-gray-600 whitespace-pre-line">{{ $campus['address'] }}</p>@endif
                            </div>
                        </div>
                        @if ($campus['map'])
                            <iframe src="{{ $campus['map'] }}" title="{{ __('office::office.page.find_us') }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" class="block w-full h-80 border-0"></iframe>
                        @endif
                    </section>
                @endif
            @endif

            @if ($contact['contacts'] !== [])
                <section aria-labelledby="office-who-to-contact">
                    <h3 id="office-who-to-contact" class="text-xl md:text-2xl font-bold text-neutral-800 mb-5">{{ __('office::office.support.plural') }}</h3>
                    <div class="space-y-3">
                        @foreach ($contact['contacts'] as $item)
                            <div class="bg-gray-50 rounded-xl p-5">
                                <p class="font-semibold text-gray-900">{{ $item['purpose'] }}</p>
                                @if ($item['location'])<p class="mt-1 inline-flex items-center gap-2 text-sm text-gray-600"><x-theme::icon name="map-pin" class="w-4 h-4 text-gray-400" />{{ $item['location'] }}</p>@endif
                                @foreach ($item['people'] as $person)
                                    <p class="mt-2 text-sm text-gray-700"><strong class="text-gray-900">{{ $person->name() }}</strong>@if ($person->designation()) <span class="text-gray-500">— {{ $person->designation() }}</span>@endif
                                        @if ($person->phone()) · <a href="tel:{{ $person->phone() }}" class="text-primary">{{ $person->phone() }}</a>@endif
                                        @if ($person->email()) · <a href="mailto:{{ $person->email() }}" class="text-primary break-all">{{ $person->email() }}</a>@endif
                                    </p>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    @endforeach
@endif
