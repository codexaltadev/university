{{--
    DIU office page (DIUFronend app/(public)/office/[slug]): hero with the office name; "Browse by Section"
    with Overview and the office's own menu pages, then Quick Links; beside it the Overview card with
    the contact facts, the Office Team (first few, View Full Team → /team) and the FAQs (All FAQs → /faq).
    Beyond DIU, what the office fills in Admin is not lost: the Head's message, Services and Sections.
    Contract: $entity (Office), $context, $page (OfficePage: the parts worked out)
--}}
@include('theme::partials.diu.hero', [
    'title' => $entity->name,
    'subtitle' => e(__('office::office.page.site_suffix', ['name' => $entity->name, 'site' => settings('general.site_name')])),
    'image' => $page->image()?->url('large') ?? theme_image('page_hero_image', 'images/page-hero.webp'),
    'breadcrumbs' => [['label' => __('theme::theme.home'), 'url' => url('/')], ['label' => $entity->name, 'url' => $page->url()]],
])

<div class="min-h-screen bg-gray-50/40" x-data="{ current: 'overview', more: false }">
    <div class="container mx-auto px-6 py-12">
        <div class="flex flex-col lg:flex-row gap-12">
            @include('theme::partials.diu.browse-sidebar', [
                'items' => [
                    ['id' => 'overview', 'label' => __('office::office.page.overview'), 'url' => null],
                    ...collect($page->menu())->map(fn ($link, $i) => ['id' => 'menu-'.$i, 'label' => $link['label'], 'url' => $link['url']])->all(),
                ],
                'quickLinks' => $page->quickLinks(),
                'width' => 'lg:w-[300px] 2xl:w-[320px]',
            ])

            <div class="flex-1 min-w-0 space-y-10">
                {{-- Overview and contact (none filled in, no card) --}}
                @if ($page->overview() || $page->facts() !== [])
                <section class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 sm:p-8" aria-labelledby="office-overview">
                    <h2 id="office-overview" class="text-2xl sm:text-3xl font-bold text-secondary mb-6">{{ __('office::office.page.overview') }}</h2>
                    @if ($overview = $page->overview())
                        @include('theme::partials.page.body', ['body' => $overview])
                    @endif
                    @if (($facts = $page->facts()) !== [])
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
                    @endif
                </section>
                @endif

                {{-- The Head's message --}}
                @if ($message = $page->headMessage())
                    <section class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 sm:p-8" aria-labelledby="office-head">
                        <h2 id="office-head" class="text-2xl sm:text-3xl font-bold text-secondary mb-6">{{ __('office::office.fields.head_message') }}</h2>
                        <div class="flex flex-col sm:flex-row gap-6 items-start">
                            @if ($head = $message['person'])
                                <div class="sm:w-48 shrink-0 text-center">
                                    @if ($head->photoUrl())<img loading="lazy" decoding="async" src="{{ $head->photoUrl() }}" alt="{{ $head->name() }}" class="w-32 h-32 mx-auto rounded-full object-cover border-2 border-gray-200">@endif
                                    <p class="mt-3 font-bold text-gray-900">{{ $head->name() }}</p>
                                    @if ($head->designation())<p class="text-sm text-gray-500">{{ $head->designation() }}</p>@endif
                                </div>
                            @endif
                            <div class="flex-1 min-w-0 text-gray-700">@include('theme::partials.page.body', ['body' => $message['message']])</div>
                        </div>
                    </section>
                @endif

                {{-- Services --}}
                @if (($services = $page->services()) !== [])
                    <section class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 sm:p-8" aria-labelledby="office-services" x-data="{ open: null }">
                        <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
                            <div>
                                <h2 id="office-services" class="text-2xl sm:text-3xl font-bold text-secondary">{{ __('office::office.services.plural') }}</h2>
                                <p class="text-gray-500 mt-1">{{ __('office::office.page.services_text') }}</p>
                            </div>
                            <a href="{{ url('/services') }}" class="text-primary font-semibold hover:underline inline-flex items-center gap-1">
                                {{ __('office::office.page.all_services') }} <x-theme::icon name="arrow-right" class="w-4 h-4" />
                            </a>
                        </div>
                        <div class="space-y-3">
                            @foreach ($services as $service)
                                <div id="service-{{ $service['id'] }}" class="border border-gray-200 rounded-xl overflow-hidden">
                                    <button type="button" class="w-full flex items-center gap-3 p-4 text-left cursor-pointer hover:bg-gray-50"
                                            @click="open = open === {{ $loop->index }} ? null : {{ $loop->index }}" :aria-expanded="open === {{ $loop->index }}">
                                        <span class="w-7 h-7 shrink-0 rounded-md bg-primary/10 text-primary flex items-center justify-center"><x-theme::icon name="clipboard" class="w-4 h-4" /></span>
                                        <span class="flex-1 font-semibold text-gray-800">{{ $service['title'] }}</span>
                                        <x-theme::icon name="chevron-down" class="w-5 h-5 text-gray-500 transition-transform" ::class="open === {{ $loop->index }} && 'rotate-180'" />
                                    </button>
                                    <div x-show="open === {{ $loop->index }}" x-cloak class="px-4 pb-4 pl-14 text-gray-600 space-y-4">
                                        @if ($service['description'])@include('theme::partials.page.body', ['body' => $service['description']])@endif
                                        @if ($service['documents'])
                                            <div>
                                                <h3 class="font-semibold text-gray-800 mb-1">{{ __('office::office.fields.required_documents') }}</h3>
                                                @include('theme::partials.page.body', ['body' => $service['documents']])
                                            </div>
                                        @endif
                                        @if ($service['fee'] || $service['processing_time'])
                                            <dl class="grid grid-cols-[max-content_1fr] gap-x-4 gap-y-1 text-sm">
                                                @if ($service['fee'])<dt class="font-semibold text-gray-500">{{ __('office::office.fields.fee') }}</dt><dd>{{ $service['fee'] }}</dd>@endif
                                                @if ($service['processing_time'])<dt class="font-semibold text-gray-500">{{ __('office::office.fields.processing_time') }}</dt><dd>{{ $service['processing_time'] }}</dd>@endif
                                            </dl>
                                        @endif
                                        @foreach ($service['people'] as $person)
                                            <p class="text-sm">{{ __('office::office.handled_by') }}: <strong class="text-gray-800">{{ $person->name() }}</strong>@if ($person->phone()) · {{ $person->phone() }}@endif @if ($person->email()) · <a href="mailto:{{ $person->email() }}" class="text-primary">{{ $person->email() }}</a>@endif</p>
                                        @endforeach
                                        @if ($service['apply_url'] || $service['form_url'])
                                            <div class="flex flex-wrap gap-3">
                                                @if ($service['apply_url'])<a href="{{ $service['apply_url'] }}" target="_blank" rel="noopener" class="px-5 py-2 rounded-full bg-primary text-white font-semibold hover:no-underline">{{ __('office::office.apply') }}</a>@endif
                                                @if ($service['form_url'])<a href="{{ $service['form_url'] }}" target="_blank" rel="noopener" class="px-5 py-2 rounded-full border border-primary text-primary font-semibold hover:no-underline inline-flex items-center gap-2"><x-theme::icon name="download" class="w-4 h-4" />{{ __('office::office.fields.form') }}</a>@endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- Office Team --}}
                @if ($page->team()->isNotEmpty())
                    <section class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 sm:p-8" aria-labelledby="office-team">
                        <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
                            <div>
                                <h2 id="office-team" class="text-2xl sm:text-3xl font-bold text-secondary">{{ __('office::office.page.team') }}</h2>
                                <p class="text-gray-500 mt-1">{{ __('office::office.page.team_text') }}</p>
                            </div>
                            <a href="{{ $page->url('team') }}" class="text-primary font-semibold hover:underline inline-flex items-center gap-1">
                                {{ __('office::office.page.view_team') }} <x-theme::icon name="arrow-right" class="w-4 h-4" />
                            </a>
                        </div>
                        <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-6">
                            @foreach ($page->teamPreview() as $person)
                                @include('theme::partials.office.member', ['person' => $person])
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- Sections (desks) --}}
                @if (($sections = $page->sections()) !== [])
                    <section class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 sm:p-8" aria-labelledby="office-sections">
                        <h2 id="office-sections" class="text-2xl sm:text-3xl font-bold text-secondary mb-6">{{ __('office::office.sections_list.plural') }}</h2>
                        <div class="grid sm:grid-cols-2 gap-4">
                            @foreach ($sections as $section)
                                <article class="bg-gray-50/80 rounded-xl p-5">
                                    <h3 class="font-bold text-gray-900">{{ $section['name'] }}</h3>
                                    @if ($section['description'])<p class="text-sm text-gray-600 mt-1">{{ $section['description'] }}</p>@endif
                                    @foreach ($section['people'] as $person)
                                        <p class="text-sm text-gray-700 mt-2">{{ $person->name() }}@if ($person->designation()) <span class="text-gray-500">— {{ $person->designation() }}</span>@endif</p>
                                    @endforeach
                                    @if ($section['phone'] || $section['email'])
                                        <div class="mt-3 space-y-1 text-sm">
                                            @if ($section['phone'])<a href="tel:{{ $section['phone'] }}" class="flex items-center gap-2 text-gray-600 hover:text-primary"><x-theme::icon name="phone" class="w-4 h-4 text-primary" />{{ $section['phone'] }}</a>@endif
                                            @if ($section['email'])<a href="mailto:{{ $section['email'] }}" class="flex items-center gap-2 text-gray-600 hover:text-primary break-all"><x-theme::icon name="mail" class="w-4 h-4 text-primary shrink-0" />{{ $section['email'] }}</a>@endif
                                        </div>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- FAQs --}}
                @if ($page->faqPreview() !== [])
                    <section class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 sm:p-8" aria-labelledby="office-faqs" x-data="{ open: 0 }">
                        <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
                            <div>
                                <h2 id="office-faqs" class="text-2xl sm:text-3xl font-bold text-secondary">{{ __('office::office.page.faqs') }}</h2>
                                <p class="text-gray-500 mt-1">{{ __('office::office.page.faqs_text') }}</p>
                            </div>
                            <a href="{{ $page->url('faq') }}" class="text-primary font-semibold hover:underline inline-flex items-center gap-1">
                                {{ __('office::office.page.all_faqs') }} <x-theme::icon name="arrow-right" class="w-4 h-4" />
                            </a>
                        </div>
                        <div class="space-y-3">
                            @foreach ($page->faqPreview() as $faq)
                                <div class="border border-gray-200 rounded-xl overflow-hidden">
                                    <button type="button" class="w-full flex items-center gap-3 p-4 text-left cursor-pointer hover:bg-gray-50"
                                            @click="open = open === {{ $loop->index }} ? null : {{ $loop->index }}" :aria-expanded="open === {{ $loop->index }}">
                                        <span class="w-7 h-7 shrink-0 rounded-md bg-primary/10 text-primary text-sm font-bold flex items-center justify-center">Q</span>
                                        <span class="flex-1 font-semibold text-gray-800">{{ $faq['question'] }}</span>
                                        <x-theme::icon name="chevron-down" class="w-5 h-5 text-gray-500 transition-transform" ::class="open === {{ $loop->index }} && 'rotate-180'" />
                                    </button>
                                    <div x-show="open === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif class="px-4 pb-4 pl-14 text-gray-600">
                                        @include('theme::partials.page.body', ['body' => $faq['answer']])
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>
        </div>
    </div>
</div>
