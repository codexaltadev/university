{{-- Partner and recognition logos (theme section, source partners.list): grey until hovered. Contract: $section (title, subheading, content: eyebrow, button_label, button_url), $items (Partner) --}}
@if (collect($items ?? [])->isNotEmpty())
    <div class="container">
        @if ($section->title || ($section->content['eyebrow'] ?? null))
            <div class="mb-8 text-center">
                @if ($section->content['eyebrow'] ?? null)<span class="eyebrow">{{ $section->content['eyebrow'] }}</span>@endif
                @if ($section->title)<h2 class="section-head__title">{{ $section->title }}</h2>@endif
            </div>
        @endif
        <div class="logo-row">
            @foreach ($items as $partner)
                @if ($logo = $partner->firstMedia('logo'))
                    <a href="{{ $partner->website_url ?: url($partner->url()) }}" @if ($partner->website_url) target="_blank" rel="noopener" @endif title="{{ $partner->name }}">
                        <img src="{{ $logo->url('medium') }}" alt="{{ $partner->name }}" loading="lazy">
                    </a>
                @endif
            @endforeach
        </div>
        @if (filled($section->content['button_url'] ?? null))
            <p class="mt-8 text-center"><a href="{{ $section->content['button_url'] }}" class="btn">{{ $section->content['button_label'] ?: __('theme::theme.view_all') }}</a></p>
        @endif
    </div>
@endif
