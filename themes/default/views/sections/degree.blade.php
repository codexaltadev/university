{{--
    DIU Degree ("Find Your Degree at DIU"): title, line, link; big links; three bordered buttons.
    Contract: $section (title, subheading, content: heading, subheading, button_label, button_url, links[label, url], actions[label, url])
--}}
<section class="px-6 py-20 lg:px-12 bg-surface text-slate-900">
    <div class="container mx-auto px-0">
        <div class="flex flex-col lg:flex-row items-start justify-between gap-10 mb-16">
            <div>
                <x-theme::heading tag="h2" color="primary">{{ $section->title ?: $section->content['heading'] }}</x-theme::heading>
                <p class="text-lg md:text-[22px] text-neutral-900 mb-4">{{ $section->subheading ?: $section->content['subheading'] }}</p>
                @if (filled($section->content['button_url']))
                    <x-theme::link-button :href="$section->content['button_url']" variant="primary">{{ $section->content['button_label'] }}</x-theme::link-button>
                @endif
            </div>
            <div class="w-full md:max-w-2/4">
                @foreach ($section->content['links'] as $link)
                    <div class="group cursor-pointer">
                        <a href="{{ $link['url'] ?: '#' }}" class="flex items-center py-8 border-b-2 border-secondary group-hover:border-secondary transition-colors hover:no-underline">
                            <h3 class="text-xl md:text-[40px] font-extrabold text-slate-800 group-hover:text-secondary transition-colors pr-4">{{ $link['label'] }}</h3>
                            <x-theme::icon name="arrow-up-right" class="w-5 h-5 text-secondary group-hover:translate-x-1 transition-all flex-shrink-0" />
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
        @if (count($section->content['actions']))
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach ($section->content['actions'] as $action)
                    <a href="{{ $action['url'] ?: '#' }}" class="flex items-center justify-center gap-2 py-9 px-6 border border-secondary rounded-lg text-neutral-900 hover:border-blue-600 hover:text-white hover:no-underline transition-all group min-h-[60px] hover:bg-[linear-gradient(98.74deg,color-mix(in_srgb,var(--color-primary)_80%,transparent)_5.6%,color-mix(in_srgb,var(--color-secondary)_80%,transparent)_100%)]">
                        <span class="font-bold text-xl md:text-2xl">{{ $action['label'] }}</span>
                        <x-theme::icon name="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
