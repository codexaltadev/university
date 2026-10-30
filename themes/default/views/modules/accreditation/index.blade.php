{{--
    /accreditations: accreditations and memberships side by side (owner 2026-10-25), each card tagged with its category
    (Accredited dark, Membership light) — logo, title, awarding body, since / valid till; in the partner cards' look. Title,
    line and picture from Settings › Accreditation. Contract: the module's (groups, base), $page (RecognitionPage)
--}}
<x-theme::layout :title="$page->text('accreditations_title') ?? __('accreditation::accreditation.plural')">
    @include('theme::partials.diu.hero', [
        'title' => $page->text('accreditations_title') ?? __('accreditation::accreditation.plural'),
        'subtitle' => ($line = $page->text('accreditations_text')) ? e($line) : null,
        'image' => $page->image() ?? theme_image('page_hero_image', 'images/page-hero.webp'),
        'breadcrumbs' => $page->breadcrumbs($page->text('accreditations_title') ?? __('accreditation::accreditation.plural'), 'accreditations'),
    ])

    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
        <div class="max-w-7xl mx-auto px-4 py-12">
            @if (($cards = $page->accreditations()) === [])
                <p class="text-center py-16 text-slate-600">{{ __('accreditation::accreditation.none') }}</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($cards as $card)
                        <a href="{{ $card['url'] }}" class="accreditation-card group bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden border border-slate-200 hover:border-secondary hover:-translate-y-1 hover:no-underline">
                            <div class="aspect-video bg-gradient-to-br from-slate-50 to-blue-50 flex items-center justify-center p-6 relative">
                                @if ($card['tag'])
                                    <span @class(['accreditation-tag absolute top-2 left-2 rounded-full px-2.5 py-0.5 text-[11px] font-semibold', 'bg-secondary text-white' => $card['accredited'], 'bg-primary/10 text-primary' => ! $card['accredited']])>{{ $card['tag'] }}</span>
                                @endif
                                @if ($card['logo'])
                                    <img src="{{ $card['logo'] }}" alt="{{ $card['title'] }}" loading="lazy" class="max-w-full max-h-full object-contain transition-transform duration-300 group-hover:scale-110">
                                @else
                                    <x-theme::icon name="graduation-cap" class="w-12 h-12 text-slate-300" />
                                @endif
                            </div>
                            <div class="p-5">
                                <h3 class="font-semibold text-slate-800 mb-2 line-clamp-2 leading-snug group-hover:text-secondary transition-colors">{{ $card['title'] }}</h3>
                                @if ($card['expired'])<p class="mb-2"><span class="rounded-full bg-red-50 px-2.5 py-0.5 text-[11px] font-semibold text-red-700">{{ __('accreditation::accreditation.expired') }}</span></p>@endif
                                @if ($card['agency'])<p class="text-sm text-slate-500 mb-2">{{ __('accreditation::accreditation.page.agency') }}: {{ $card['agency'] }}</p>@endif
                                @if ($card['since'] || $card['until'])
                                    <p class="text-xs text-slate-500 flex flex-wrap gap-3">
                                        @if ($card['since'])<span>{{ __('accreditation::accreditation.page.awarded', ['date' => $card['since']]) }}</span>@endif
                                        @if ($card['until'])<span>{{ __('accreditation::accreditation.page.until', ['date' => $card['until']]) }}</span>@endif
                                    </p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-theme::layout>
