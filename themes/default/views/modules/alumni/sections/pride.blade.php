{{--
    Homepage section "Our Pride Alumni" with DIU's alumni cards — a sliding row or a grid, as chosen — and a round outline
    button to all alumni. Contract: $items (Alumnus list), $section, $settings (count, display), $page (AlumniPage)
--}}
@if (($cards = $page->cards()) !== [])
    <div @class(['alumni-pride', 'flex gap-6 overflow-x-auto snap-x snap-mandatory pb-4' => $page->slider(), 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8' => ! $page->slider()])>
        @foreach ($cards as $card)
            <div @class(['snap-start shrink-0 w-[260px]' => $page->slider()])>
                @include('theme::partials.alumni.card', ['card' => $card])
            </div>
        @endforeach
    </div>
    <div class="mt-8 flex justify-center">
        <x-theme::button :href="$page->listingUrl()" variant="outlineSecondary" rounded="full" padding="px-6 py-3" size="text-base md:text-lg">{{ __('alumni::alumni.all_alumni') }}</x-theme::button>
    </div>
@endif
