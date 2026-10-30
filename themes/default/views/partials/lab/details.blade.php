{{--
    DIU LabDetails (components/Lab/LabDetails.js): "Laboratory" badge, name, text; Location and
    Instruments; Assigned Person; Gallery. Beyond DIU, what the lab has entered in Admin: its team,
    equipment / scopes / projects / products, video and contact. Contract: $lab (LabPage)
--}}
<div class="space-y-6">
    <div class="border border-gray-200 rounded-lg overflow-hidden bg-white">
        <div class="p-6 sm:p-8">
            <div class="mb-8">
                <div class="inline-block px-3 py-1 rounded-md bg-gray-100 text-gray-700 text-xs font-semibold uppercase tracking-wider mb-4">{{ $lab->type() ?? __('lab::lab.page.laboratory') }}</div>
                <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4 leading-tight">
                    <a href="{{ $lab->lab->url() }}" class="text-inherit hover:no-underline">{{ $lab->lab->name }}</a>
                </h1>
                @if ($description = $lab->description())
                    <div class="text-gray-600 leading-relaxed text-base">@include('theme::partials.page.body', ['body' => $description])</div>
                @endif
            </div>

            @if ($lab->location() || $lab->instruments())
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8 pb-8 border-b border-gray-200">
                    @if ($location = $lab->location())
                        <div class="border border-gray-200 rounded-lg p-5">
                            <div class="flex items-start gap-3">
                                <x-theme::icon name="map-pin" class="w-5 h-5 text-gray-400 shrink-0 mt-0.5" />
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-semibold text-gray-900 mb-1 text-sm">{{ __('lab::lab.page.location') }}</h3>
                                    <p class="text-sm text-gray-600 leading-relaxed">{{ $location }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                    @if ($instruments = $lab->instruments())
                        <div class="border border-gray-200 rounded-lg p-5">
                            <div class="flex items-start gap-3">
                                <x-theme::icon name="wrench" class="w-5 h-5 text-gray-400 shrink-0 mt-0.5" />
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-semibold text-gray-900 mb-1 text-sm">{{ __('lab::lab.page.instruments') }}</h3>
                                    <div class="text-sm text-gray-600 leading-relaxed">@include('theme::partials.page.body', ['body' => $instruments])</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            @if ($lab->people()->isNotEmpty())
                <div>
                    <h3 class="font-semibold text-gray-900 text-lg mb-4">{{ __('lab::lab.page.assigned') }}</h3>
                    <div class="space-y-4">
                        @foreach ($lab->people() as $person)
                            <div class="border border-gray-200 rounded-lg p-5">
                                <div class="flex flex-col sm:flex-row gap-6">
                                    <div class="flex items-start gap-3 flex-1">
                                        <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center shrink-0 overflow-hidden">
                                            @if ($person->photoUrl())<img loading="lazy" decoding="async" src="{{ $person->photoUrl() }}" alt="" class="w-full h-full object-cover">@else<x-theme::icon name="user" class="w-5 h-5 text-gray-600" />@endif
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-900 text-base mb-0.5">{{ $person->name() }}</p>
                                            <p class="text-sm text-gray-600">{{ $person->designation() }}</p>
                                        </div>
                                    </div>
                                    @if ($person->phone() || $person->email())
                                        <div class="space-y-2 sm:min-w-[240px]">
                                            @if ($person->phone())<a href="tel:{{ $person->phone() }}" class="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 transition-colors"><x-theme::icon name="phone" class="w-4 h-4" /><span>{{ $person->phone() }}</span></a>@endif
                                            @if ($person->email())<a href="mailto:{{ $person->email() }}" class="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 transition-colors"><x-theme::icon name="mail" class="w-4 h-4" /><span class="truncate">{{ $person->email() }}</span></a>@endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if (($explore = $lab->explore()) !== [] || $lab->contact() !== [])
        <div class="border border-gray-200 rounded-lg overflow-hidden bg-white">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">{{ $explore !== [] ? __('lab::lab.page.explore') : __('lab::lab.page.contact') }}</h2>
            </div>
            <div class="p-6 space-y-6">
                @if ($explore !== [])
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach ($explore as $link)
                            <a href="{{ $link['url'] }}" class="group flex items-center justify-between border border-gray-200 rounded-lg px-4 py-3 text-gray-800 font-medium hover:border-gray-900 hover:no-underline transition-colors">
                                <span>{{ $link['label'] }} <span class="text-gray-400">({{ $link['count'] }})</span></span>
                                <x-theme::icon name="chevron-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                            </a>
                        @endforeach
                    </div>
                @endif
                @if (($contact = $lab->contact()) !== [])
                    <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-gray-600">
                        @if (isset($contact['phone']))<a href="tel:{{ $contact['phone'] }}" class="flex items-center gap-2 hover:text-gray-900"><x-theme::icon name="phone" class="w-4 h-4" />{{ $contact['phone'] }}</a>@endif
                        @if (isset($contact['email']))<a href="mailto:{{ $contact['email'] }}" class="flex items-center gap-2 hover:text-gray-900"><x-theme::icon name="mail" class="w-4 h-4" />{{ $contact['email'] }}</a>@endif
                        @if (isset($contact['website']))<a href="{{ $contact['website'] }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 hover:text-gray-900"><x-theme::icon name="globe" class="w-4 h-4" />{{ __('lab::lab.page.website') }}</a>@endif
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if ($video = $lab->video())
        <div class="border border-gray-200 rounded-lg overflow-hidden bg-white">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50"><h2 class="text-lg font-semibold text-gray-900">{{ __('lab::lab.page.video') }}</h2></div>
            <div class="p-6"><div class="aspect-video rounded-lg overflow-hidden"><iframe src="{{ $video }}" title="{{ $lab->lab->name }}" class="w-full h-full border-0" allowfullscreen loading="lazy"></iframe></div></div>
        </div>
    @endif

    @if (($gallery = $lab->gallery()) !== [])
        <div class="border border-gray-200 rounded-lg overflow-hidden bg-white">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50"><h2 class="text-lg font-semibold text-gray-900">{{ __('lab::lab.page.gallery') }}</h2></div>
            <div class="p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($gallery as $image)
                        <div class="aspect-video rounded-lg overflow-hidden bg-gray-50 border border-gray-200">
                            <img decoding="async" src="{{ $image['url'] }}" alt="{{ $image['caption'] ?? $lab->lab->name }}" loading="lazy" class="w-full h-full object-cover">
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
