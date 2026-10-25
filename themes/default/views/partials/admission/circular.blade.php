{{--
    One admission circular as DIU's notice (NoticeCardNew): green while open, grey once closed; name (a link on the list),
    Open / Closed, each date with Active / Upcoming / Expired. Contract: $circular (AdmissionBlocks::card), $link (bool)
--}}
<article @class(['rounded-xl border px-5 py-4', 'bg-green-50 border-green-200' => ! $circular['closed'], 'bg-slate-50 border-slate-200' => $circular['closed']])>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            @if ($link)
                <h2 class="text-lg md:text-xl font-semibold text-neutral-900"><a href="{{ $circular['url'] }}" class="hover:underline">{{ $circular['title'] }}</a></h2>
            @else
                <p class="text-lg md:text-xl font-semibold text-neutral-900">{{ $circular['title'] }}</p>
            @endif
            @if ($circular['session'])<p class="text-sm text-slate-600">{{ $circular['session'] }}</p>@endif
        </div>
        <span @class(['shrink-0 rounded-full px-3 py-1 text-sm font-medium', 'bg-green-600 text-white' => ! $circular['closed'], 'bg-slate-200 text-slate-600' => $circular['closed']])>{{ __($circular['closed'] ? 'admission::admission.closed' : 'admission::admission.open') }}</span>
    </div>
    @if ($circular['rows'] !== [])
        <div class="mt-3">
            @foreach ($circular['rows'] as $row)
                <div class="flex gap-1 md:gap-4 md:items-center justify-between py-2 text-base border-b border-gray-400 last:border-b-0 text-gray-700">
                    <span><span class="font-bold text-sm md:text-lg">{{ $row['label'] }}:</span> <time datetime="{{ $row['iso'] }}" class="ml-1">{{ $row['date'] }}</time></span>
                    <span @class(['px-4 py-2 text-sm md:text-base shrink-0 flex items-center', 'rounded-full border border-gray-800' => ! $row['past']])>{{ $row['status'] }}</span>
                </div>
            @endforeach
        </div>
    @endif
</article>
