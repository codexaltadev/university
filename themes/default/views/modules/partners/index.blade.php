{{--
    /partners as DIU's /mps/{category} (DIUFronend app/(public)/mps): hero; the types as tabs (and the discount partners);
    a logo card per organization — the logo on a soft panel growing on hover, the name, the website's domain, a line.
    Title and line from Settings › Partners. Contract: the module's (partners, types, type, hasDiscounts, base), $page (PartnersPage)
--}}
<x-theme::layout :title="$page->text('partners_title') ?? __('partners::partners.plural')">
    @include('theme::partials.diu.hero', [
        'title' => $page->text('partners_title') ?? __('partners::partners.plural'),
        'subtitle' => ($line = $page->text('partners_text')) ? e($line) : null,
        'image' => $page->image() ?? theme_image('page_hero_image', 'images/page-hero.webp'),
        'breadcrumbs' => $page->breadcrumbs($page->text('partners_title') ?? __('partners::partners.plural'), 'partners'),
    ])

    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
        <div class="max-w-7xl mx-auto px-4 py-12">
            @if (($tabs = $page->tabs()) !== [])
                <nav class="flex flex-wrap gap-2 mb-8" aria-label="{{ __('partners::partners.fields.partner_type') }}">
                    @foreach ($tabs as $tab)
                        <a href="{{ $tab['url'] }}" @if ($tab['current']) aria-current="page" @endif
                           @class(['px-5 py-2.5 rounded-full text-sm font-medium border transition-colors hover:no-underline',
                                   'bg-secondary text-white border-secondary' => $tab['current'], 'bg-white text-slate-700 border-slate-200 hover:border-secondary hover:text-secondary' => ! $tab['current']])>{{ $tab['label'] }}</a>
                    @endforeach
                </nav>
            @endif

            @if (($logos = $page->logos()) === [])
                <p class="text-center py-16 text-slate-600">{{ __('partners::partners.page.none') }}</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach ($logos as $logo)
                        <a @if ($logo['url']) href="{{ $logo['url'] }}" target="_blank" rel="noopener" @endif
                           class="partner-card group bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden border border-slate-200 hover:border-secondary hover:-translate-y-1 hover:no-underline">
                            <div class="aspect-video bg-gradient-to-br from-slate-50 to-blue-50 flex items-center justify-center p-6 relative overflow-hidden">
                                @if ($logo['logo'])
                                    <img src="{{ $logo['logo'] }}" alt="{{ $logo['name'] }}" loading="lazy" class="max-w-full max-h-full object-contain transition-transform duration-300 group-hover:scale-110">
                                @else
                                    <x-theme::icon name="building" class="w-12 h-12 text-slate-300" />
                                @endif
                                <span @class(['partner-tag absolute top-2 left-2 rounded-full px-2.5 py-0.5 text-[11px] font-semibold',
                                              'bg-secondary text-white' => $logo['kind'] === 'accreditation', 'bg-primary/10 text-primary' => $logo['kind'] !== 'accreditation'])>{{ $logo['type'] }}</span>
                                @if ($logo['international'])<span class="absolute top-2 right-2 inline-flex items-center gap-1 rounded-full bg-white/90 px-2 py-0.5 text-[11px] font-medium text-secondary"><x-theme::icon name="globe" class="w-3 h-3" />{{ __('partners::partners.fields.is_international') }}</span>@endif
                            </div>
                            <div class="p-4">
                                <h3 class="font-semibold text-slate-800 mb-2 line-clamp-2 text-sm leading-snug group-hover:text-secondary transition-colors">{{ $logo['name'] }}</h3>
                                @if ($logo['text'])<p class="text-xs text-slate-500 line-clamp-3 mb-2">{{ $logo['text'] }}</p>@endif
                                @if ($logo['domain'])
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-slate-500 flex items-center gap-1"><x-theme::icon name="globe" class="w-3 h-3" />{{ $logo['domain'] }}</span>
                                        <x-theme::icon name="external" class="w-4 h-4 text-secondary opacity-0 group-hover:opacity-100 transition-opacity" />
                                    </div>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-theme::layout>
