{{-- One link video: embed, title, episode, speaker, duration. Contract: $video --}}
@php($speaker = $video->speaker())
<figure class="gallery-video" style="margin:0">
    @if ($embed = $video->embedUrl())
        <div style="position:relative;aspect-ratio:16/9"><iframe src="{{ $embed }}" title="{{ $video->title ?: __('gallery::gallery.video') }}" loading="lazy" allowfullscreen
            allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" style="position:absolute;inset:0;width:100%;height:100%;border:0;border-radius:var(--radius-sm, 4px)"></iframe></div>
    @else
        <a href="{{ $video->url }}" target="_blank" rel="noopener">{{ $video->title ?: $video->url }}</a>
    @endif
    <figcaption style="margin-top:var(--space-2, 8px)">
        @if ($video->episode_no)<small>{{ __('gallery::gallery.episode', ['no' => $video->episode_no]) }}</small><br>@endif
        @if ($video->title)<strong>{{ $video->title }}</strong>@endif
        @if ($speaker)<br><small>{{ $speaker->name() }}@if ($speaker->designation()), {{ $speaker->designation() }}@endif</small>@endif
        @if ($video->duration())<small style="color:var(--color-muted, #6b7280)"> · {{ $video->duration() }}</small>@endif
    </figcaption>
</figure>
