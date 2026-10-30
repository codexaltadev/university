{{--
    DIU HeroSection: video behind the floating header, navy fade at the bottom, title and text.
    Contract: $section (title, subheading, content: video, poster, heading, subheading)
--}}
<section class="relative min-h-[70vh] bg-black md:min-h-[75vh] 2xl:min-h-[80vh] flex items-center justify-center pt-56 md:pt-48" style="margin-top: calc(-1 * var(--header-h, 0px))">
    @if (filled($section->content['video'] ?? null))
        <video autoplay loop muted playsinline preload="auto" @if ($section->content['poster']) poster="{{ $section->content['poster']['url'] }}" @endif class="absolute inset-0 w-full h-full object-cover z-0">
            {{-- With a poster, phones keep to the picture: the video is megabytes on mobile data (Lighthouse, Phase 4.8c) --}}
            <source src="{{ $section->content['video'] }}" type="video/mp4" @if ($section->content['poster']) media="(min-width: 768px)" @endif>
        </video>
    @elseif ($section->content['poster'])
        <img src="{{ $section->content['poster']['url'] }}" alt="{{ $section->content['poster']['alt'] }}" fetchpriority="high" class="absolute inset-0 w-full h-full object-cover z-0">
    @endif
    <div class="max-sm:block hidden absolute inset-0 z-10 bg-[linear-gradient(180deg,transparent_44.17%,var(--color-secondary)_99.44%),linear-gradient(0deg,transparent_1.5%,rgb(0_0_0/0.2)_81.06%)]"></div>
    <div class="absolute top-[45%] inset-0 bg-gradient-to-t from-secondary via-secondary/50 to-transparent z-10"></div>
    <div class="relative z-10 text-white px-4 md:px-0 container mx-auto w-full">
        <x-theme::reveal>
            <div class="lg:pl-1 max-w-2xl">
                <x-theme::heading tag="h1" color="white" size="xl">{{ $section->title ?: $section->content['heading'] }}</x-theme::heading>
                <p class="text-lg md:text-[28px] opacity-90 max-w-4xl">{{ $section->subheading ?: $section->content['subheading'] }}</p>
            </div>
        </x-theme::reveal>
    </div>
</section>
