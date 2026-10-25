{{--
    Admission circulars as DIU's NoticeCardNew (/admission): a green card "List of Admission Announce Notices" that folds,
    each circular's name and its dates — Last day to apply, Admission test … — with Active / Upcoming / Expired.
    Contract: $data (running_only, limit), $locale; $admission (AdmissionBlocks)
--}}
<section class="rounded-xl border bg-green-50 border-green-200 text-green-900 px-4 py-3" x-data="{ open: true }" aria-live="polite">
    <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open" class="flex w-full items-start justify-between gap-4 text-left cursor-pointer">
        <span class="flex items-center gap-3">
            <x-theme::icon name="alert-circle" class="mt-0.5 h-5 w-5 shrink-0" />
            <span class="font-medium text-gray-900">{{ __('admission::admission.notices_title') }}</span>
        </span>
        <x-theme::icon name="chevron-down" class="w-8 h-6 text-gray-700 transition-transform duration-200 shrink-0" x-bind:class="open && 'rotate-180'" />
    </button>
    <div x-show="open" x-collapse>
        @if (($circulars = $admission->circulars()) === [])
            <p class="mt-4 text-sm text-slate-600">{{ __('admission::admission.no_circulars') }}</p>
        @else
            <div class="mt-4 grid gap-5">
                @foreach ($circulars as $circular)
                    <div class="space-y-2">
                        <a href="{{ $circular['url'] }}" class="block text-black font-medium text-base hover:underline">{{ $circular['title'] }}</a>
                        @foreach ($circular['rows'] as $row)
                            <div class="flex gap-1 md:gap-4 md:items-center justify-between py-2 text-base border-b border-gray-400 text-gray-700">
                                <span><span class="font-bold text-sm md:text-lg">{{ $row['label'] }}:</span> <time datetime="{{ $row['iso'] }}" class="ml-1">{{ $row['date'] }}</time></span>
                                <span @class(['px-4 py-2 text-sm md:text-base shrink-0 flex items-center', 'rounded-full border border-gray-800' => ! $row['past']])>{{ $row['status'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif
        <a href="{{ $admission->circularsUrl() }}" class="mt-4 mb-1 inline-flex items-center gap-1 rounded-full border border-gray-800 px-3 py-1.5 text-sm text-gray-900 hover:bg-white/60 hover:no-underline">
            <x-theme::icon name="arrow-up-right" class="h-4 w-4" /> {{ __('admission::admission.all_circulars') }}
        </a>
    </div>
</section>
