{{-- Blue band with two links (theme options footer_band_*); hidden while the first text is empty --}}
@if (filled(theme_option('footer_band_1_label')))
    <section class="px-6 py-12 lg:px-12 bg-primary">
        <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-center justify-between gap-6">
            @foreach ([['video', 1], ['vr', 2]] as [$icon, $n])
                @if (filled(theme_option("footer_band_{$n}_label")))
                    <a href="{{ theme_option("footer_band_{$n}_url") ?: '#' }}" target="_blank" rel="noopener" class="flex items-center gap-2 text-white hover:text-blue-200 hover:no-underline transition-colors">
                        <x-theme::icon :name="$icon" width="36" height="36" />
                        <span class="font-bold text-lg md:text-[24px]">{{ theme_option("footer_band_{$n}_label") }} →</span>
                    </a>
                @endif
            @endforeach
        </div>
    </section>
@endif
