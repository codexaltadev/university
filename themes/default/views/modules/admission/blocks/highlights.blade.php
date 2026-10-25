{{--
    Admission information cards as DIU's /admission "Necessary Documents during Admission": each category's heading and its
    cards as DIU's round outline buttons (↗ when they open another site). Contract: $data (categories, grouped), $locale; $admission (AdmissionBlocks)
--}}
<div class="space-y-6">
    @foreach ($admission->highlights() as $group)
        <div>
            @if ($group['category'])<h3 class="text-xl md:text-2xl font-semibold text-neutral-800">{{ $group['category'] }}</h3>@endif
            <div class="flex flex-wrap gap-2 mt-4 text-secondary">
                @foreach ($group['items'] as $item)
                    @if ($item['url'])
                        <x-theme::button :href="$item['url']" :target="$item['external'] ? '_blank' : null" variant="outlineSecondary" rounded="full" padding="px-4 py-2" size="text-base">
                            @if ($item['image'])<img src="{{ $item['image'] }}" alt="" loading="lazy" class="size-5 object-contain">@endif
                            {{ $item['title'] }}
                            @if ($item['external'])<x-theme::icon name="arrow-up-right" class="w-4 h-4" />@endif
                        </x-theme::button>
                    @else
                        <span class="inline-flex items-center gap-2 rounded-full border border-neutral-300 px-4 py-2 text-base text-neutral-700">{{ $item['title'] }}</span>
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach
</div>
