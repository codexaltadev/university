{{-- No homepage set up yet (FR-HOME-08): the site name and tagline as a hero --}}
<section class="hero">
    <div class="container">
        <h1 class="hero__title max-w-4xl">{{ settings('general.site_name') }}</h1>
        @if ($tagline = settings('general.tagline'))
            <p class="hero__text">{{ $tagline }}</p>
        @endif
    </div>
</section>
