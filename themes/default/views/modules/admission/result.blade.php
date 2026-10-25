{{--
    /admission/result as DIU's /admission-test-result: the title and line on a light-blue page, the lookup in a white card
    (with the answer), then "Admission Information" — a picture beside link cards and Apply Online Now — and "Need
    Assistance?" (email, helpline, office, online application). Texts and links from Settings › Admission.
    Contract: $intro; $page (AdmissionPage); the form is the admission.result-lookup Livewire component
--}}
<x-theme::layout :title="__('admission::admission.result_page.title')">
    <main class="min-h-screen bg-gradient-to-br from-blue-50 to-white px-4 pt-32 pb-10" style="margin-top: calc(-1 * var(--header-h, 0px))">
        <div class="container mx-auto">
            <div class="flex flex-col items-center mt-10 md:mt-16">
                <h1 class="text-3xl md:text-4xl font-bold mb-4 text-center text-primary">{{ __('admission::admission.result_page.title') }}</h1>
                @if ($text = $page->intro())
                    <div class="text-gray-600 text-lg text-center max-w-2xl [&_a]:text-secondary [&_a]:underline">{!! $text !!}</div>
                @else
                    <p class="text-gray-500 text-base text-center">{{ __('admission::admission.result_view.lead') }}</p>
                @endif
            </div>
            <livewire:admission.result-lookup />
        </div>

        <section class="container mx-auto py-12">
            <div class="flex flex-col lg:flex-row">
                @if ($image = $page->resultImage())
                    <div class="lg:w-1/2 p-4 md:p-8"><img src="{{ $image }}" alt="" loading="lazy" class="rounded-xl w-full object-cover shadow-md"></div>
                @endif
                <div @class(['p-4 md:p-8', 'lg:w-1/2' => $image, 'w-full' => ! $image])>
                    <h2 class="text-2xl font-bold mb-6 text-primary">{{ __('admission::admission.result_view.info') }}</h2>
                    <div @class(['grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8', 'lg:grid-cols-3' => ! $image])>
                        @foreach ($page->resultLinks() as $link)
                            <a href="{{ $link['url'] }}" @if (str_starts_with($link['url'], 'http') && ! str_starts_with($link['url'], url('/'))) target="_blank" rel="noopener noreferrer" @endif
                               class="group flex items-center justify-between p-4 rounded-xl border bg-white border-gray-100 shadow-sm hover:shadow-md hover:border-primary/30 transition-all duration-300 hover:no-underline">
                                <span class="text-gray-700 group-hover:text-primary font-medium">{{ $link['label'] }}</span>
                                <x-theme::icon name="chevron-right" class="w-5 h-5 text-gray-400 group-hover:text-primary group-hover:translate-x-1 transition-all duration-300" />
                            </a>
                        @endforeach
                    </div>
                    @if ($apply = $page->applyUrl())
                        <a href="{{ $apply }}" target="_blank" rel="noopener" class="inline-flex rounded-md bg-secondary px-8 py-3 font-semibold text-white hover:bg-primary hover:no-underline">{{ __('admission::admission.result_view.apply') }}</a>
                    @endif
                </div>
            </div>
        </section>

        @if (($office = $page->office()) && ($office['email'] || $office['helpline'] || $office['address']))
            <section class="container mx-auto">
                <div class="rounded-2xl bg-white p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">{{ __('admission::admission.contact_page.assistance') }}</h2>
                    <dl class="grid gap-6 md:grid-cols-2 text-gray-700">
                        @if ($office['email'])
                            <div class="flex gap-3"><x-theme::icon name="mail" class="size-5 mt-0.5 text-primary" /><div><dt class="font-medium text-gray-900">{{ __('admission::admission.contact_page.email') }}</dt><dd><a href="mailto:{{ $office['email'] }}" class="hover:underline">{{ $office['email'] }}</a></dd></div></div>
                        @endif
                        @if ($office['address'])
                            <div class="flex gap-3"><x-theme::icon name="building" class="size-5 mt-0.5 text-primary" /><div><dt class="font-medium text-gray-900">{{ __('admission::admission.contact_page.office') }}</dt><dd>{{ $office['name'] }}@if ($office['name'] && $office['address']), @endif{{ $office['address'] }}</dd></div></div>
                        @endif
                        @if ($office['helpline'])
                            <div class="flex gap-3"><x-theme::icon name="phone" class="size-5 mt-0.5 text-primary" /><div><dt class="font-medium text-gray-900">{{ __('admission::admission.contact_page.helpline') }}</dt><dd><a href="tel:{{ $office['tel'] }}" class="hover:underline">{{ $office['helpline'] }}</a></dd></div></div>
                        @endif
                        @if ($apply)
                            <div class="flex gap-3"><x-theme::icon name="globe" class="size-5 mt-0.5 text-primary" /><div><dt class="font-medium text-gray-900">{{ __('admission::admission.contact_page.online') }}</dt><dd><a href="{{ $apply }}" target="_blank" rel="noopener" class="hover:underline break-all">{{ $apply }}</a></dd></div></div>
                        @endif
                    </dl>
                </div>
            </section>
        @endif
    </main>
</x-theme::layout>
