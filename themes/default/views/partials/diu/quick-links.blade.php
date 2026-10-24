{{-- DIU QuickLinks: link icon, "Quick Links", round outline buttons. Contract: $links (list of [label, url]) --}}
@if (count($links))
    <div class="mt-6 rounded-xl">
        <div class="pb-2 border-b border-gray-300 flex items-center gap-2">
            <x-theme::icon name="link" class="w-4 h-4" />
            <h3 class="text-xl font-bold text-neutral-600">{{ __('theme::theme.quick_links') }}</h3>
        </div>
        <div class="flex w-full flex-wrap mt-4 gap-2">
            @foreach ($links as $link)
                <x-theme::button :href="$link['url']" :target="str_starts_with((string) $link['url'], 'http') ? '_blank' : null" variant="outlineSecondary" rounded="full" padding="px-4 py-[10px]" size="text-base md:text-lg" class="min-w-max">{{ $link['label'] }}</x-theme::button>
            @endforeach
        </div>
    </div>
@endif
