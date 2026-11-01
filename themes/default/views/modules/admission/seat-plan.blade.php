{{--
    /admission/seat-plan as DIU's /seat-plan (DIUFronend seat-plan/page): "DIU Admission Test" badge, "Admission Test Seat
    Plan", the line under it; the lookup (search card, the seat, the buildings — the applicant's marked); the help bar
    (email, helpline). Texts and pictures from Settings › Admission. Contract: $page (AdmissionPage); the form is the
    admission.seat-lookup Livewire component
--}}
<x-theme::layout :title="__('admission::admission.seats.page_title')">
    <div class="min-h-screen bg-gradient-to-br from-blue-50/70 via-slate-50 to-white pt-28 pb-20 px-4 sm:px-6 lg:px-8" style="margin-top: calc(-1 * var(--header-h, 0px))">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mt-6 mb-8 sm:mt-10 sm:mb-10">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-100/80 text-secondary text-xs sm:text-sm font-semibold mb-3">
                    <x-theme::icon name="building" class="w-4 h-4 text-primary" />
                    <span>{{ __('admission::admission.seats.badge') }}</span>
                </div>
                <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-secondary tracking-tight">{{ __('admission::admission.seats.page_title') }}</h1>
                @if ($intro = $page->seatIntro())
                    <div class="text-gray-600 text-sm sm:text-base mt-2.5 max-w-xl mx-auto leading-relaxed [&_a]:text-primary [&_a]:underline">{!! $intro !!}</div>
                @else
                    <p class="text-gray-600 text-sm sm:text-base mt-2.5 max-w-xl mx-auto leading-relaxed">{{ __('admission::admission.seats.lead') }}</p>
                @endif
            </div>

            <livewire:admission.seat-lookup />

            @if ($page->office()['email'] || $page->office()['helpline'])
                <div class="mt-10 bg-white rounded-2xl border border-gray-200/80 p-5 sm:p-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-secondary">{{ __('admission::admission.seats.help') }}</h2>
                        <p class="text-xs sm:text-sm text-gray-500 mt-0.5">{{ __('admission::admission.seats.help_text') }}</p>
                    </div>
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        @if ($page->office()['email'])
                            <a href="mailto:{{ $page->office()['email'] }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 border border-gray-300 text-xs sm:text-sm font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                <x-theme::icon name="mail" class="w-4 h-4 text-gray-500" /><span>{{ __('admission::admission.result_view.email') }}</span>
                            </a>
                        @endif
                        @if ($page->office()['helpline'])
                            <a href="tel:{{ $page->office()['tel'] }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-white bg-secondary hover:bg-primary transition-colors shadow-sm">
                                <x-theme::icon name="phone" class="w-4 h-4" /><span>{{ $page->office()['helpline'] }}</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-theme::layout>
