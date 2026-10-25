{{--
    Apply / Request for Information as DIU's /admission links ("Visit Our Program"): an optional heading and line, Apply Now
    as DIU's button, the others as DIU's LinkButton (↗ with a growing underline). Contract: $data (heading, text, buttons), $locale; $admission (AdmissionBlocks)
--}}
<div>
    @if (filled($data['heading'] ?? null))<h2 class="text-2xl md:text-[34px] font-semibold text-neutral-800">{{ $data['heading'] }}</h2>@endif
    @if (filled($data['text'] ?? null))<p class="mt-1 max-w-3xl text-lg text-slate-600">{{ $data['text'] }}</p>@endif
    <div class="mt-4 flex flex-wrap items-center gap-x-8 gap-y-4">
        @foreach ($admission->links() as $link)
            @if ($link['primary'])
                <x-theme::button :href="$link['url']" :target="$link['external'] ? '_blank' : null" variant="primary" rounded="full" padding="px-6 py-3">{{ $link['label'] }}</x-theme::button>
            @else
                <x-theme::link-button :href="$link['url']" :target="$link['external'] ? '_blank' : null" variant="primary">{{ $link['label'] }}</x-theme::link-button>
            @endif
        @endforeach
    </div>
</div>
