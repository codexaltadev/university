{{--
    <x-platform::breadcrumb :context="$context" /> — the microsite trail (UI/UX Part B §3 v0.2):
    Home › Faculty › Department › Program / Lab, each to its microsite, with BreadcrumbList
    JSON-LD. Nothing on the main site. Themes place it in their "breadcrumb" partial.
--}}
@props(['context' => null])
@php
    $trail = \Platform\Context\Breadcrumbs::for($context ?? \Platform\Context\SiteContext::site());
    $jsonLd = count($trail) > 1 ? json_encode([
        '@context' => 'https://schema.org', '@type' => 'BreadcrumbList',
        'itemListElement' => collect($trail)->values()->map(fn ($c, $i) => array_filter(['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c['label'], 'item' => $c['url'] ? url($c['url']) : null]))->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;
@endphp
@if (count($trail) > 1)
    <nav {{ $attributes->merge(['class' => 'breadcrumb']) }} aria-label="{{ __('platform::relations.breadcrumb') }}">
        <ol>
            @foreach ($trail as $crumb)
                <li>
                    @if (! $loop->last && $crumb['url'])
                        <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a><span aria-hidden="true"> › </span>
                    @else
                        <span aria-current="page">{{ $crumb['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
    <script type="application/ld+json">{!! $jsonLd !!}</script>
@endif
