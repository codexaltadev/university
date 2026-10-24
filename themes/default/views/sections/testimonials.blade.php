{{--
    "What People Say" — the chosen testimonials (owner 2026-10-25), in DIU's testimonial card look (the
    /testimonials page): heading, intro and "View All"; three columns of cards — the start of their
    words, photo, name, title, type, the country when from abroad; each opens its testimonial on
    /testimonials. (DIU's own homepage slider of distinguished guests is the section "visitors".)
    Contract: $section (title, subheading, content: heading, subheading, button_label, button_url), $items (Testimonial, the chosen ones)
--}}
@if ($items && count($items))
    <x-theme::reveal>
        <section class="px-4 md:px-6 py-10 lg:px-12 md:py-16 bg-gray-50 text-slate-900">
            <div class="container mx-auto px-0">
                <div class="flex flex-col lg:flex-row items-start justify-between gap-4">
                    <div class="md:max-w-3/5">
                        <x-theme::heading tag="h2" color="primary">{{ $section->title ?: $section->content['heading'] }}</x-theme::heading>
                        @if (filled($section->subheading ?: $section->content['subheading']))
                            <p class="mt-3 text-neutral-500 text-base sm:text-[24px]">{{ $section->subheading ?: $section->content['subheading'] }}</p>
                        @endif
                    </div>
                    @if (filled($section->content['button_url']))
                        <x-theme::button :href="$section->content['button_url']" variant="outlinePrimary" :right-icon="true">{{ $section->content['button_label'] }}</x-theme::button>
                    @endif
                </div>

                <div class="mt-10 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($items as $testimonial)
                        <a href="{{ url('/'.trim($section->content['button_url'] ?: 'testimonials', '/')).'#testimonial-'.$testimonial->getKey() }}"
                           class="group bg-white rounded-lg p-6 shadow-sm border border-gray-200 hover:shadow-md hover:border-gray-300 transition-all duration-200 flex flex-col hover:no-underline">
                            <x-theme::icon name="quote" class="w-8 h-8 text-primary/30 mb-3" />
                            <p class="text-gray-700 leading-relaxed mb-6 line-clamp-5 text-sm flex-1">{{ \Illuminate\Support\Str::limit(trim(strip_tags((string) $testimonial->quote)), 260) }}</p>
                            <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
                                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center shrink-0 overflow-hidden">
                                    @if ($photo = $testimonial->photoUrl())<img src="{{ $photo }}" alt="{{ $testimonial->person()?->name() }}" loading="lazy" class="w-full h-full object-cover">@else<x-theme::icon name="user" class="w-6 h-6 text-gray-400" />@endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-gray-900 truncate">{{ $testimonial->person()?->name() }}</h3>
                                    @if ($title = $testimonial->person()?->assignment->role_label ?: $testimonial->person()?->designation())<p class="text-sm text-gray-600 truncate">{{ $title }}</p>@endif
                                </div>
                                <span class="flex flex-col items-end gap-1 shrink-0">
                                    <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded">{{ __('testimonials::testimonials.types.'.$testimonial->testimonial_type) }}</span>
                                    @if ($country = $testimonial->countryAbroad())<span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-blue-50 text-blue-700 rounded"><x-theme::icon name="globe" class="w-3 h-3" />{{ $country }}</span>@endif
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    </x-theme::reveal>
@endif
