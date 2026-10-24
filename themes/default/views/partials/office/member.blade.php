{{-- DIU OfficeTeamSection member card: round photo, name, designation, then phone and email. Contract: $person (PersonView) --}}
<div class="bg-white rounded-xl border border-gray-200 p-5 text-center hover:shadow-md transition-shadow">
    <div class="w-20 h-20 mx-auto rounded-full overflow-hidden border-2 border-gray-200 bg-gray-100 flex items-center justify-center">
        @if ($person->photoUrl())
            <img src="{{ $person->photoUrl() }}" alt="{{ $person->name() }}" loading="lazy" class="w-full h-full object-cover">
        @else
            <x-theme::icon name="user" class="w-8 h-8 text-gray-400" />
        @endif
    </div>
    <h3 class="mt-4 font-bold text-gray-900">
        @if ($person->profileUrl())<a href="{{ $person->profileUrl() }}" class="hover:text-primary">{{ $person->name() }}</a>@else{{ $person->name() }}@endif
    </h3>
    @if ($person->designation())<p class="text-sm text-gray-500 mt-1">{{ $person->designation() }}</p>@endif
    @if ($person->phone() || $person->email())
        <div class="mt-4 pt-4 border-t border-gray-100 space-y-1.5 text-sm text-left">
            @if ($person->phone())
                <a href="tel:{{ $person->phone() }}" class="flex items-center gap-2 text-gray-600 hover:text-primary"><x-theme::icon name="phone" class="w-4 h-4 shrink-0 text-primary" /><span class="truncate">{{ $person->phone() }}</span></a>
            @endif
            @if ($person->email())
                <a href="mailto:{{ $person->email() }}" class="flex items-center gap-2 text-gray-600 hover:text-primary"><x-theme::icon name="mail" class="w-4 h-4 shrink-0 text-primary" /><span class="truncate">{{ $person->email() }}</span></a>
            @endif
        </div>
    @endif
</div>
