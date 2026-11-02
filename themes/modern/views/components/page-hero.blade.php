{{--
    <x-theme::page-hero :title="…" :text="…" :crumbs="[[label, url], …]" :image="url" eyebrow="…"> actions </x-theme::page-hero>
    The band at the top of every inner page (UI/UX Part B §12.4): breadcrumb, title, intro, optional picture.
--}}
@props(['title', 'text' => null, 'crumbs' => [], 'image' => null, 'eyebrow' => null])
<section class="page-hero">
    <div @class(['container page-hero__grid', 'page-hero__grid--media' => $image])>
        <div>
            @if (count($crumbs))
                <nav class="breadcrumb" aria-label="{{ __('theme::theme.breadcrumb') }}">
                    <ol>
                        <li><a href="{{ url('/') }}">{{ __('theme::theme.home') }}</a><span aria-hidden="true"> / </span></li>
                        @foreach ($crumbs as $crumb)
                            <li>
                                @if (! $loop->last && ($crumb['url'] ?? null))
                                    <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a><span aria-hidden="true"> / </span>
                                @else
                                    <span aria-current="page">{{ $crumb['label'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </nav>
            @endif
            @if ($eyebrow)<span class="eyebrow">{{ $eyebrow }}</span>@endif
            <h1 class="page-hero__title">{{ $title }}</h1>
            @if (filled($text))<p class="page-hero__text">{{ $text }}</p>@endif
            {{ $slot }}
        </div>
        @if ($image)
            <div class="page-hero__media"><img src="{{ $image }}" alt="" fetchpriority="high"></div>
        @endif
    </div>
</section>
