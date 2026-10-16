{{--
    /gallery/{slug} (FR-GAL-02/04/05). Contract: $album, $photos (['item', 'caption'] list, album order), $uploads (uploaded videos, same shape),
    $videos (link videos, in order), $layout (masonry / grid / carousel), $base (microsite path or '')
--}}
<x-theme::layout :title="$album->title">
    <article class="container" style="padding-block:var(--space-10, 40px)">
        <p><a href="{{ url($base.'/'.\Modules\Gallery\Models\Album::PATH) }}">← {{ __('gallery::gallery.plural') }}</a></p>
        <h1>{{ $album->title }}</h1>
        <p style="color:var(--color-muted, #6b7280)">
            @if ($album->occurred_on){{ $album->occurred_on->translatedFormat('j F Y') }}@endif
            @if ($album->series_type) · {{ __("gallery::gallery.series.{$album->series_type}") }}@endif
        </p>
        @if ($album->description)<p>{{ $album->description }}</p>@endif

        @if ($photos->isNotEmpty())
            <section style="margin-top:var(--space-6, 24px)">
                @include('gallery::partials.photos', ['photos' => $photos, 'layout' => $layout])
            </section>
        @endif

        @if ($uploads->isNotEmpty() || $videos->isNotEmpty())
            <section style="margin-top:var(--space-8, 32px)">
                @if ($photos->isNotEmpty())<h2>{{ __('gallery::gallery.videos') }}</h2>@endif
                <div style="display:grid;gap:var(--space-5, 20px);grid-template-columns:repeat(auto-fill,minmax(320px,1fr))">
                    @foreach ($uploads as $upload)
                        <figure style="margin:0">
                            <video controls preload="metadata" src="{{ $upload['item']->url() }}" style="width:100%;border-radius:var(--radius-sm, 4px)"></video>
                            @if ($upload['caption'])<figcaption>{{ $upload['caption'] }}</figcaption>@endif
                        </figure>
                    @endforeach
                    @foreach ($videos as $video)
                        @include('gallery::partials.video', ['video' => $video])
                    @endforeach
                </div>
            </section>
        @endif

        @if ($photos->isEmpty() && $uploads->isEmpty() && $videos->isEmpty())
            <p>{{ __('gallery::gallery.empty_album') }}</p>
        @endif
    </article>
</x-theme::layout>
