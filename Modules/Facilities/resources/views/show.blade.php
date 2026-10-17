{{-- /facilities/{slug} (FR-FCL-03). Contract: $facility, $gallery (MediaItem list), $base --}}
<x-theme::layout :title="$facility->title">
    <article class="container" style="padding-block:var(--space-10, 40px);max-width:960px">
        <p><a href="{{ url($base.'/'.\Modules\Facilities\Models\Facility::PATH) }}">← {{ __('facilities::facilities.plural') }}</a></p>
        <h1>{{ $facility->title }}</h1>
        @if ($image = $facility->firstMedia('image'))
            <img src="{{ $image->url('large') ?? $image->url() }}" alt="{{ $image->alt_text ?: $facility->title }}" style="width:100%;border-radius:var(--radius, 8px)">
        @endif
        @if ($facility->summary)<p style="font-size:1.1rem;margin-top:var(--space-4, 16px)">{{ $facility->summary }}</p>@endif
        <div class="prose">{!! $facility->body !!}</div>
        @if ($gallery->isNotEmpty())
            <section style="margin-top:var(--space-8, 32px)">
                @if (view()->exists('gallery::partials.photos'))
                    @include('gallery::partials.photos', ['photos' => $gallery->map(fn ($item) => ['item' => $item, 'caption' => $item->caption ?: null]), 'layout' => 'grid'])
                @else
                    <div style="display:grid;gap:var(--space-3, 12px);grid-template-columns:repeat(auto-fill,minmax(180px,1fr))">
                        @foreach ($gallery as $item)<img src="{{ $item->url('medium') ?? $item->url() }}" alt="{{ $item->alt_text ?? '' }}" loading="lazy" style="width:100%;aspect-ratio:4/3;object-fit:cover">@endforeach
                    </div>
                @endif
            </section>
        @endif
        @if ($facility->external_url)
            <p style="margin-top:var(--space-6, 24px)"><a class="button" href="{{ $facility->external_url }}" @if ($facility->open_in_new_tab) target="_blank" rel="noopener" @endif>{{ __('facilities::facilities.more') }}</a></p>
        @endif
    </article>
</x-theme::layout>
