{{-- No homepage set up yet (FR-HOME-08) --}}
<section class="home-section">
    <h1>{{ settings('general.site_name') }}</h1>
    @if ($tagline = settings('general.tagline'))
        <p>{{ $tagline }}</p>
    @endif
</section>
