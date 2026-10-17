{{--
    DIU Faculty States: headline, line and the faculty-members link on the left; four figures in a
    two-by-two grid with blue rules on the right.
    Contract: $section (title, subheading, content: heading, subheading, link_label, link_url, stats[number, label]),
    $items (the faculty's own counters: label, display — used instead of content stats when there are any)
--}}
<div class="bg-white py-20">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-16 items-start">
            <div>
                <x-theme::heading tag="h2" size="sm">{{ $section->title ?: $section->content['heading'] }}</x-theme::heading>
                <p class="text-gray-600 mb-8 text-lg md:text-[22px]">{{ $section->subheading ?: $section->content['subheading'] }}</p>
                @if (filled($section->content['link_url']))
                    <a href="{{ $section->content['link_url'] }}" @if (str_starts_with((string) $section->content['link_url'], 'http')) target="_blank" rel="noopener noreferrer" @endif class="flex items-center gap-2 text-primary font-semibold hover:text-secondary transition-colors text-lg md:text-[24px]">{{ $section->content['link_label'] }} →</a>
                @endif
            </div>

            @if (count($stats = collect($items ?? [])->isNotEmpty() ? collect($items)->map(fn ($s) => ['number' => $s['display'], 'label' => $s['label']])->all() : $section->content['stats']))
                <div class="grid grid-cols-2 gap-0">
                    @foreach (array_slice($stats, 0, 4) as $stat)
                        <div @class(['text-center py-8 px-6 border-sky-700','border-r' => $loop->index % 2 === 0, 'border-b' => $loop->index < 2])>
                            <div class="text-5xl font-extrabold text-primary mb-3">{{ $stat['number'] }}</div>
                            <div class="text-base md:text-[24px] text-neutral-600 leading-tight">{{ $stat['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
