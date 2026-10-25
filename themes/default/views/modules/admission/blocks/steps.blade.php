{{-- How to apply: numbered steps in DIU's card look. Contract: $data, $locale; $admission (AdmissionBlocks) --}}
@if (($steps = $admission->steps()) !== [])
    <div>
        <h3 class="text-xl md:text-2xl font-semibold text-neutral-800">{{ __('admission::admission.how_to_apply') }}</h3>
        <ol class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($steps as $step)
                <li class="relative rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <span class="grid place-items-center size-9 rounded-full bg-primary text-white font-semibold">{{ $loop->iteration }}</span>
                    <h4 class="mt-3 text-lg font-semibold text-neutral-800">{{ $step['title'] }}</h4>
                    @if ($step['text'])<p class="mt-1 text-slate-600">{{ $step['text'] }}</p>@endif
                </li>
            @endforeach
        </ol>
    </div>
@endif
