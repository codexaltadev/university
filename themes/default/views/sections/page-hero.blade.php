{{--
    DIU Academics Hero: the microsite shown — cover behind the floating header, navy fade,
    breadcrumb, name, about. On the main site: the section's heading and subheading.
    Contract: $section (title, subheading, content: show_about, about_right, image), $context
--}}
<header class="page-hero relative isolate overflow-hidden pt-20 lg:pt-24 2xl:pt-60 pb-10 md:pb-10" aria-label="{{ $context->identity()['name'] ?? $section->title }}" style="margin-top: calc(-1 * var(--header-h, 0px))">
    <div class="absolute inset-0 -z-10 bg-secondary">
        @if ($cover = $context->entity?->firstMedia('cover') ?? $context->entity?->firstMedia('photo'))
            <img src="{{ $cover->url('large') ?? $cover->url() }}" alt="" fetchpriority="high" decoding="async" class="h-full w-full object-cover object-top">
        @elseif ($section->content['image'])
            <picture>
                @if ($small = theme_image_small($section->content['image']['url']))<source media="(max-width: 767px)" srcset="{{ $small }}">@endif
                <img src="{{ $section->content['image']['url'] }}" alt="" fetchpriority="high" decoding="async" class="h-full w-full object-cover object-top">
            </picture>
        @endif
        <div class="absolute top-[15%] inset-0 bg-gradient-to-t from-secondary via-secondary/80 to-transparent z-10"></div>
    </div>
    <x-theme::reveal>
        <div class="mx-auto container px-4 sm:px-6 lg:px-8 pt-6 pb-20 max-sm:pb-0">
            @if (count($trail = \Platform\Context\Breadcrumbs::for($context)) > 1)
                <nav aria-label="{{ __('platform::relations.breadcrumb') }}">
                    <ol class="flex items-center gap-2 text-sm text-surface">
                        @foreach ($trail as $crumb)
                            <li class="flex items-center gap-2">
                                @if (! $loop->last && $crumb['url'])
                                    <a href="{{ $crumb['url'] }}" class="hover:underline line-clamp-1 text-sm rounded">{{ $crumb['label'] }}</a>
                                @else
                                    <span class="line-clamp-1 text-sm text-gray-300" aria-current="page">{{ $crumb['label'] }}</span>
                                @endif
                                @if (! $loop->last)<span aria-hidden="true"> / </span>@endif
                            </li>
                        @endforeach
                    </ol>
                </nav>
            @endif
            <x-theme::heading tag="h1" size="xl" color="white" class="mt-4 max-sm:text-[24px] pb-3 line-clamp-2">{{ $context->identity()['name'] ?? ($section->title ?: settings('general.site_name')) }}</x-theme::heading>
            @if ($section->content['show_about'])
                @if ($about = $context->entity?->about ?? $context->entity?->overview ?? $context->entity?->summary)
                    <div @class(['mt-4 max-w-3xl text-base sm:text-xl text-white/90 [&_p]:m-0', 'float-end' => $section->content['about_right']])>{!! \Illuminate\Support\Str::sanitizeHtml((string) $about) !!}</div>
                @elseif ($section->subheading)
                    <p @class(['mt-4 max-w-3xl text-base sm:text-xl text-white/90', 'float-end' => $section->content['about_right']])>{{ $section->subheading }}</p>
                @endif
            @endif
        </div>
    </x-theme::reveal>
</header>
