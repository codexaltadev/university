{{-- Logo; on a microsite also its name beside it (DIU: blue rule + Mina, from 1280px; UI/UX Part B §4.2) --}}
<div class="site-brand relative flex items-center gap-2">
    <a href="{{ url('/') }}" class="site-brand__logo shrink-0"><x-platform::logo variant="light" class="site-logo w-44 2xl:w-56 h-auto" /></a>
    @if ($identity = $context->identity())
        <span class="hidden xl:block bg-sky-700 h-[60px] w-[2px]" aria-hidden="true"></span>
        <a href="{{ $identity['url'] ?? url()->current() }}" class="site-brand__name microsite-name hidden xl:block leading-6 font-mina text-[24px] max-w-[350px] font-bold text-primary hover:no-underline">{{ $identity['name'] }}</a>
    @endif
</div>
