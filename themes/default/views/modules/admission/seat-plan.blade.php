{{--
    /admission/seat-plan as DIU's /seat-plan (DIUFronend seat-plan/page): "DIU Admission Test" badge, "Admission Test Seat
    Plan", the line under it; the lookup (search card, the seat, the buildings — the applicant's marked); the help bar
    (email, helpline). Texts and pictures from Settings › Admission. The page and the card are every lookup's
    (x-theme::lookup-page, x-theme::lookup-form). Contract: $page (AdmissionPage); the form is the admission.seat-lookup
    Livewire component
--}}
<x-theme::lookup-page :title="__('admission::admission.seats.page_title')" :badge="__('admission::admission.seats.badge')" icon="building"
    :intro="$page->seatIntro()" :lead="__('admission::admission.seats.lead')">
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
</x-theme::lookup-page>
