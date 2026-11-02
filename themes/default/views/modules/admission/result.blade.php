{{--
    /admission/result as DIU's /admission-test-result: the badge, title and line, the lookup in a white card (with the
    answer), then "Admission Information" — a picture beside link cards and Apply Online Now — and "Need Assistance?"
    (email, helpline, office, online application). Texts and links from Settings › Admission. The page and the card are
    every lookup's (x-theme::lookup-page, x-theme::lookup-form), as the seat plan's.
    Contract: $intro; $page (AdmissionPage); the form is the admission.result-lookup Livewire component
--}}
<x-theme::lookup-page :title="__('admission::admission.result_page.title')" :badge="__('admission::admission.seats.badge')" icon="graduation-cap"
    :intro="$page->intro()" :lead="__('admission::admission.result_view.lead')">
    <livewire:admission.result-lookup />

    <section class="mt-10 bg-white rounded-2xl border border-gray-200/80 p-5 sm:p-6">
        <div class="flex flex-col lg:flex-row gap-6">
            @if ($image = $page->resultImage())
                <div class="lg:w-2/5"><img src="{{ $image }}" alt="" loading="lazy" class="rounded-xl w-full h-full object-cover"></div>
            @endif
            <div @class(['lg:w-3/5' => $image, 'w-full' => ! $image])>
                <h2 class="text-lg sm:text-xl font-bold text-secondary mb-4">{{ __('admission::admission.result_view.info') }}</h2>
                <div @class(['grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6', 'lg:grid-cols-3' => ! $image])>
                    @foreach ($page->resultLinks() as $link)
                        <a href="{{ $link['url'] }}" @if (str_starts_with($link['url'], 'http') && ! str_starts_with($link['url'], url('/'))) target="_blank" rel="noopener noreferrer" @endif
                           class="group flex items-center justify-between px-4 py-3 rounded-xl border bg-white border-gray-200 hover:border-primary/30 hover:shadow-sm transition-all duration-300 hover:no-underline">
                            <span class="text-sm text-gray-700 group-hover:text-primary font-medium">{{ $link['label'] }}</span>
                            <x-theme::icon name="chevron-right" class="w-4 h-4 text-gray-400 group-hover:text-primary group-hover:translate-x-1 transition-all duration-300" />
                        </a>
                    @endforeach
                </div>
                @if ($apply = $page->applyUrl())
                    <a href="{{ $apply }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-white bg-secondary hover:bg-primary transition-colors hover:no-underline">{{ __('admission::admission.result_view.apply') }}</a>
                @endif
            </div>
        </div>
    </section>

    @if (($office = $page->office()) && ($office['email'] || $office['helpline'] || $office['address']))
        <section class="mt-10 bg-white rounded-2xl border border-gray-200/80 p-5 sm:p-6">
            <h2 class="text-lg sm:text-xl font-bold text-secondary mb-5">{{ __('admission::admission.contact_page.assistance') }}</h2>
            <dl class="grid gap-6 md:grid-cols-2 text-sm text-gray-700">
                @if ($office['email'])
                    <div class="relative pl-8"><dt class="font-medium text-gray-900"><x-theme::icon name="mail" class="absolute left-0 top-0.5 size-5 text-primary" />{{ __('admission::admission.contact_page.email') }}</dt><dd><a href="mailto:{{ $office['email'] }}" class="hover:underline">{{ $office['email'] }}</a></dd></div>
                @endif
                @if ($office['address'])
                    <div class="relative pl-8"><dt class="font-medium text-gray-900"><x-theme::icon name="building" class="absolute left-0 top-0.5 size-5 text-primary" />{{ __('admission::admission.contact_page.office') }}</dt><dd>{{ $office['name'] }}@if ($office['name'] && $office['address']), @endif{{ $office['address'] }}</dd></div>
                @endif
                @if ($office['helpline'])
                    <div class="relative pl-8"><dt class="font-medium text-gray-900"><x-theme::icon name="phone" class="absolute left-0 top-0.5 size-5 text-primary" />{{ __('admission::admission.contact_page.helpline') }}</dt><dd><a href="tel:{{ $office['tel'] }}" class="hover:underline">{{ $office['helpline'] }}</a></dd></div>
                @endif
                @if ($apply)
                    <div class="relative pl-8"><dt class="font-medium text-gray-900"><x-theme::icon name="globe" class="absolute left-0 top-0.5 size-5 text-primary" />{{ __('admission::admission.contact_page.online') }}</dt><dd><a href="{{ $apply }}" target="_blank" rel="noopener" class="hover:underline break-all">{{ $apply }}</a></dd></div>
                @endif
            </dl>
        </section>
    @endif
</x-theme::lookup-page>
