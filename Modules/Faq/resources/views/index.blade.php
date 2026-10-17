{{-- /faq and /{entity}/faq (FR-FAQ-02/03/04/05). Contract: $groups (list of [category, faqs]), $selected (term or null), $faqs, $layout (accordion|grouped), $jsonLd, $helpful --}}
<x-theme::layout :title="__('faq::faq.title')">
    <div class="container faq-page" style="padding-block:var(--space-8, 32px);max-width:960px"
         x-data="{ q: '', match(el) { return this.q.trim() === '' || el.dataset.search.includes(this.q.trim().toLowerCase()) } }">
        <h1>{{ __('faq::faq.title') }}</h1>

        <label class="faq-search" style="display:block;margin-block:var(--space-4, 16px)">
            <span class="sr-only">{{ __('faq::faq.search') }}</span>
            <input type="search" x-model="q" placeholder="{{ __('faq::faq.search') }}" autocomplete="off"
                   style="width:100%;padding:var(--space-3, 12px);border:1px solid var(--color-border);border-radius:var(--radius-sm, 4px)">
        </label>

        @if ($groups->count() > 1 || $selected)
            <nav class="faq-categories" aria-label="{{ __('faq::faq.fields.category') }}" style="display:flex;flex-wrap:wrap;gap:var(--space-2, 8px);margin-bottom:var(--space-6, 24px)">
                <a href="{{ url()->current() }}" class="badge {{ $selected ? '' : 'badge--success' }}">{{ __('faq::faq.all') }}</a>
                @foreach ($groups as $group)
                    @if ($group['category'])
                        <a href="{{ url()->current().'?category='.urlencode($group['category']->slug()) }}" class="badge">{{ $group['category']->name }}</a>
                    @endif
                @endforeach
            </nav>
        @endif

        @foreach ($groups as $group)
            <section class="faq-group" style="margin-bottom:var(--space-8, 32px)">
                @if ($layout === 'grouped' || $groups->count() > 1)
                    <h2>{{ $group['category']?->name ?? __('faq::faq.other') }}</h2>
                    @if ($group['category']?->description)
                        <div class="prose" style="color:var(--color-text-muted)">{!! \Illuminate\Support\Str::sanitizeHtml((string) $group['category']->description) !!}</div>
                    @endif
                @endif
                <div style="display:grid;gap:var(--space-2, 8px)">
                    @foreach ($group['faqs'] as $faq)
                        <details id="faq-{{ $faq->id }}" class="accordion__item" x-show="match($el)"
                                 data-search="{{ mb_strtolower(strip_tags($faq->question.' '.$faq->answer)) }}"
                                 style="border:1px solid var(--color-border);border-radius:var(--radius-sm, 4px);padding:var(--space-3, 12px) var(--space-4, 16px)">
                            <summary class="accordion__title" style="cursor:pointer;font-weight:600">{{ $faq->question }}</summary>
                            <div class="accordion__body prose" style="margin-top:var(--space-3, 12px)">{!! \Illuminate\Support\Str::sanitizeHtml((string) $faq->answer) !!}</div>
                            @if ($helpful)
                                @include('faq::partials.helpful', ['faq' => $faq])
                            @endif
                        </details>
                    @endforeach
                </div>
            </section>
        @endforeach

        <p x-cloak x-show="q.trim() !== '' && ! [...$root.querySelectorAll('details[data-search]')].some(el => match(el))">{{ __('faq::faq.no_match') }}</p>
    </div>
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
</x-theme::layout>
