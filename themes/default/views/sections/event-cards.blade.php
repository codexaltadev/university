{{--
    DIU ProgramEvents: title with "View All Events", line, three event cards (picture with the date,
    title, summary, "View Details"). Hidden when there is no event.
    Contract: $section (title, subheading, content: heading, subheading, link_label, link_url, card_link), $items (Event)
--}}
@if ($items && count($items))
    <div class="bg-white py-16">
        <div class="container mx-auto px-4">
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <x-theme::heading tag="h2" size="md">{{ $section->title ?: $section->content['heading'] }}</x-theme::heading>
                    @if (filled($section->content['link_url']))
                        <a href="{{ $section->content['link_url'] }}" class="font-bold hover:underline text-secondary text-lg flex items-center gap-1">{{ $section->content['link_label'] }}</a>
                    @endif
                </div>
                <p class="text-gray-600 text-lg">{{ $section->subheading ?: $section->content['subheading'] }}</p>
            </div>
            <div class="grid md:grid-cols-3 gap-6">
                @foreach (collect($items)->take(3) as $event)
                    <div class="bg-green-50/60 relative p-3 md:p-6 rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300 pb-20 md:pb-24">
                        <div class="relative pb-4">
                            @if ($image = $event->firstMedia('featured_image'))
                                <img src="{{ $image->url('medium') ?? $image->url() }}" alt="{{ $event->title }}" loading="lazy" class="rounded-xl w-full h-[220px] object-cover">
                            @else
                                <div class="rounded-xl w-full h-[220px] bg-gray-200"></div>
                            @endif
                            <div class="absolute top-10 bg-white py-2 px-2 z-10"><span class="text-base text-neutral-800 font-semibold">{{ $event->starts_at?->format('Y-m-d') }}</span></div>
                        </div>
                        <h3 class="text-[24px] font-semibold text-neutral-800 mb-4 leading-tight">{{ $event->title }}</h3>
                        <p class="text-base text-gray-500 mb-6 leading-relaxed line-clamp-4">{{ \Illuminate\Support\Str::limit(strip_tags((string) $event->summary), 240) }}</p>
                        <div class="absolute inset-x-0 bottom-0 px-3 md:px-6 pb-3 md:pb-6 pt-2">
                            <div class="bg-gray-200 mb-3 w-full h-[0.5px]"></div>
                            <a href="{{ url($event->url()) }}" class="flex items-center gap-2 text-secondary font-medium text-[24px] hover:text-sky-700 hover:no-underline transition-colors group">
                                {{ $section->content['card_link'] }}<x-theme::icon name="arrow-up-right" class="w-4 h-4 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
