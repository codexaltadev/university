{{-- Admission contacts: each group's people as contact cards (photo, name, designation, phone with extension, email). Contract: $data (groups), $locale; $admission (AdmissionBlocks) --}}
<div class="space-y-8">
    @foreach ($admission->contacts() as $group)
        <div>
            <h3 class="text-xl md:text-2xl font-semibold text-neutral-800">{{ $group['name'] }}</h3>
            <ul class="mt-4 grid gap-4 sm:grid-cols-2" role="list">
                @foreach ($group['people'] as $person)
                    <li class="flex gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        @if ($person['photo'])
                            <img src="{{ $person['photo'] }}" alt="" loading="lazy" class="size-16 shrink-0 rounded-full object-cover">
                        @else
                            <span class="grid place-items-center size-16 shrink-0 rounded-full bg-blue-50 text-primary"><x-theme::icon name="user" class="size-7" /></span>
                        @endif
                        <div class="min-w-0 text-sm text-slate-600 space-y-0.5">
                            <p class="text-base font-semibold text-neutral-800">{{ $person['name'] }}</p>
                            @if ($person['designation'])<p>{{ $person['designation'] }}</p>@endif
                            @if ($person['phone'])<p class="flex items-center gap-1.5"><x-theme::icon name="phone" class="size-4 text-secondary" /><a href="tel:{{ $person['tel'] }}" class="hover:underline">{{ $person['phone'] }}</a>@if ($person['ext']) ({{ __('admission::admission.ext') }} {{ $person['ext'] }})@endif</p>@endif
                            @if ($person['email'])<p class="flex items-center gap-1.5 min-w-0"><x-theme::icon name="mail" class="size-4 text-secondary shrink-0" /><a href="mailto:{{ $person['email'] }}" class="truncate hover:underline">{{ $person['email'] }}</a></p>@endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</div>
