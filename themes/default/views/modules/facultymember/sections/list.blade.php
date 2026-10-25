{{--
    Homepage section "Faculty Members" (a Faculty's or Department's home) with DIU's DeanCard-style cards and a round
    outline button to the place's list. Contract: $items (members, url), $section, $settings, $page (FacultyMembersPage)
--}}
@if (($cards = $page->cards()) !== [])
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach ($cards as $card)
            @include('theme::partials.faculty-member.card', ['card' => $card])
        @endforeach
    </div>
    <div class="mt-8 flex justify-center">
        <x-theme::button :href="url($items['url'])" variant="outlineSecondary" rounded="full" padding="px-6 py-3" size="text-base md:text-lg">{{ __('facultymember::facultymember.all_members') }}</x-theme::button>
    </div>
@endif
