{{--
    A lookup page (admission test result, seat plan …), one look for all (owner 2026-11-02): the light-blue page, a badge,
    the title and the line under it, then the page's content — the lookup card first — in the site's width.
    <x-theme::lookup-page :title="…" :badge="…" icon="building" :intro="$html" :lead="…">…</x-theme::lookup-page>
    intro: sanitised HTML from Settings; lead: the plain line when there is none
--}}
@props(['title', 'badge' => null, 'icon' => 'search', 'intro' => null, 'lead' => null])
<x-theme::layout :title="$title">
    <div class="min-h-screen bg-gradient-to-br from-blue-50/70 via-slate-50 to-white pt-28 pb-20 px-4" style="margin-top: calc(-1 * var(--header-h, 0px))">
        <div class="container mx-auto">   {{-- the site's width: the edges line up with the header's --}}
            <div class="text-center mt-6 mb-8 sm:mt-10 sm:mb-10">
                @if ($badge)
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-100/80 text-secondary text-xs sm:text-sm font-semibold mb-3">
                        <x-theme::icon :name="$icon" class="w-4 h-4 text-primary" />
                        <span>{{ $badge }}</span>
                    </div>
                @endif
                <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-secondary tracking-tight">{{ $title }}</h1>
                @if (filled($intro))
                    <div class="text-gray-600 text-sm sm:text-base mt-2.5 max-w-xl mx-auto leading-relaxed [&_a]:text-primary [&_a]:underline">{!! $intro !!}</div>
                @elseif (filled($lead))
                    <p class="text-gray-600 text-sm sm:text-base mt-2.5 max-w-xl mx-auto leading-relaxed">{{ $lead }}</p>
                @endif
            </div>

            {{ $slot }}
        </div>
    </div>
</x-theme::layout>
