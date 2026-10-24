{{--
    DIU Academics Hero (components/Academics/Hero): picture behind the floating header, navy fade,
    breadcrumb, big title, subtitle. Contract: $title, $subtitle (HTML allowed), $image (url), $breadcrumbs (list of [label, url])
--}}
<header class="page-hero relative isolate overflow-hidden pt-20 lg:pt-24 2xl:pt-60 pb-10 md:pb-10" aria-label="{{ $title }}" style="margin-top: calc(-1 * var(--header-h, 0px))">
    <div class="absolute inset-0 -z-10 bg-secondary">
        @if ($image)
            <img src="{{ $image }}" alt="" class="h-full w-full object-cover shadow-none object-top">
        @endif
        <div class="absolute top-[15%] inset-0 bg-gradient-to-t from-secondary via-secondary/80 to-transparent z-10"></div>
    </div>
    <x-theme::reveal>
        <div class="mx-auto container px-4 sm:px-6 lg:px-8 pt-6 pb-20 max-sm:pb-0">
            @if (count($breadcrumbs))
                <nav aria-label="{{ __('platform::relations.breadcrumb') }}">
                    <ol class="flex items-center gap-2 text-sm text-surface">
                        @foreach ($breadcrumbs as $crumb)
                            <li class="flex items-center gap-2">
                                <a href="{{ $crumb['url'] }}" @class(['hover:underline line-clamp-1 text-sm rounded', 'text-gray-400' => $loop->last]) @if ($loop->last) aria-current="page" @endif>{{ $crumb['label'] }}</a>
                                @if (! $loop->last)<span aria-hidden="true"> / </span>@endif
                            </li>
                        @endforeach
                    </ol>
                </nav>
            @endif
            <x-theme::heading tag="h1" size="xl" color="white" class="mt-4 max-sm:text-[24px] pb-3 line-clamp-2">{{ $title }}</x-theme::heading>
            @if (filled($subtitle))
                <p class="mt-4 max-w-3xl text-base sm:text-xl text-white/90">{!! $subtitle !!}</p>
            @endif
        </div>
    </x-theme::reveal>
</header>
