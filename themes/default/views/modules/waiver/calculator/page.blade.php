{{--
    /waiver-calculator: DIU's /tuition-fee-calculator — hero, the "Important Notice" card with its link, then the
    calculator (modules/waiver/livewire/waiver-calculator). Title, line, picture and notice from Settings › Waiver Calculator.
    Contract: $page (CalculatorPage)
--}}
<x-theme::layout :title="$page->title()">
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50">
        @include('theme::partials.diu.hero', [
            'title' => $page->title(),
            'subtitle' => $page->text() ? e($page->text()) : null,
            'image' => $page->image() ?? theme_asset('images/faculty-banner.jpg'),
            'breadcrumbs' => $page->breadcrumbs(),
        ])

        @if ($notice = $page->notice())
            <div class="bg-gray-50 no-print">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                    <div class="bg-white rounded-lg shadow p-4 sm:p-5">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                            <div class="flex-1 flex items-start gap-2">
                                <span class="shrink-0 size-5 bg-amber-100 rounded-full grid place-items-center mt-0.5"><x-theme::icon name="alert-circle" class="size-3 text-amber-600" /></span>
                                <div>
                                    <h2 class="text-sm font-semibold text-gray-900 mb-1">{{ $notice['title'] }}</h2>
                                    <p class="text-gray-700 leading-snug text-xs sm:text-sm">{{ $notice['text'] }}</p>
                                </div>
                            </div>
                            @if ($notice['url'])
                                <a href="{{ $notice['url'] }}" target="_blank" rel="noopener"
                                   class="shrink-0 inline-flex items-center justify-center gap-1.5 w-full sm:w-auto px-4 py-2 text-sm bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 hover:no-underline">
                                    {{ $notice['label'] }} <x-theme::icon name="external" class="size-3.5" />
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
            <livewire:waiver.calculator />
        </div>
    </div>
</x-theme::layout>
