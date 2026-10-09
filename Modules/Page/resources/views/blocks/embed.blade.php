{{-- Video / map block. Contract: $data['url'] (YouTube, Vimeo, Google Maps, Facebook — checked on save), $data['caption'] --}}
@php($src = \Modules\Page\Blocks\EmbedBlock::embedUrl((string) ($data['url'] ?? '')))
@if ($src)
    <figure class="block block--embed">
        <div style="position:relative;padding-top:56.25%">
            <iframe src="{{ $src }}" title="{{ $data['caption'] ?? '' }}" loading="lazy" allowfullscreen
                    style="position:absolute;inset:0;width:100%;height:100%;border:0"
                    referrerpolicy="strict-origin-when-cross-origin"></iframe>
        </div>
        @if (filled($data['caption'] ?? null))
            <figcaption>{{ $data['caption'] }}</figcaption>
        @endif
    </figure>
@endif
