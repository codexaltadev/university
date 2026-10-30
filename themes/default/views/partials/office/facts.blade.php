{{-- An office's contact facts under its overview. Contract: $facts (OfficePage::facts(): key => value) --}}
<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 pt-6 mt-6 border-t border-gray-200">
    @foreach ($facts as $key => $value)
        <div class="bg-gray-50/80 rounded-xl p-4 flex items-start gap-3 min-w-0">
            <span class="w-9 h-9 shrink-0 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                <x-theme::icon :name="['location' => 'map-pin', 'phone' => 'phone', 'email' => 'mail', 'extension' => 'phone', 'hours' => 'clock'][$key]" class="w-4 h-4" />
            </span>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('office::office.page.'.$key) }}</p>
                @if ($key === 'email')
                    <a href="mailto:{{ $value }}" class="text-sm font-medium text-gray-800 break-all hover:text-primary">{{ $value }}</a>
                @elseif ($key === 'phone')
                    <a href="tel:{{ $value }}" class="text-sm font-medium text-gray-800 hover:text-primary">{{ $value }}</a>
                @else
                    <p class="text-sm font-medium text-gray-800 break-words">{{ $value }}</p>
                @endif
            </div>
        </div>
    @endforeach
</div>
