{{-- /csr/{slug} (FR-CSR-02). Contract: $activity, $gallery (MediaItem list), $base --}}
@php($category = $activity->termsIn(\Modules\Csr\Models\Activity::CATEGORY)->first())
@php($path = url($base.'/'.\Modules\Csr\Models\Activity::PATH))
<x-theme::layout :title="$activity->title">
    <article class="container" style="padding-block:var(--space-10, 40px);max-width:960px">
        <p><a href="{{ $category ? $path.'/category/'.$category->slug() : $path }}">← {{ $category?->name ?? __('csr::csr.plural') }}</a></p>
        <h1>{{ $activity->title }}</h1>
        @if ($date = $activity->occurred_on)<p><time datetime="{{ $date->toDateString() }}">{{ $date->translatedFormat('j F Y') }}</time></p>@endif
        @if ($image = $activity->firstMedia('image'))
            <img src="{{ $image->url('large') ?? $image->url() }}" alt="{{ $image->alt_text ?: $activity->title }}" style="width:100%;border-radius:var(--radius, 8px)">
        @endif
        @if ($activity->summary)<p style="font-size:1.1rem;margin-top:var(--space-4, 16px)">{{ $activity->summary }}</p>@endif
        <div class="prose">{!! $activity->body !!}</div>
        @if ($embed = $activity->videoEmbed())
            <div style="margin-top:var(--space-6, 24px);aspect-ratio:16/9"><iframe src="{{ $embed }}" title="{{ $activity->title }}" style="width:100%;height:100%;border:0" allowfullscreen loading="lazy"></iframe></div>
        @elseif ($activity->video_url)
            <p style="margin-top:var(--space-6, 24px)"><a class="button button--outline" href="{{ $activity->video_url }}" target="_blank" rel="noopener">{{ __('csr::csr.watch') }}</a></p>
        @endif
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
        @if ($activity->external_url)
            <p style="margin-top:var(--space-6, 24px)"><a class="button" href="{{ $activity->external_url }}" target="_blank" rel="noopener">{{ __('csr::csr.more') }}</a></p>
        @endif
    </article>
</x-theme::layout>
