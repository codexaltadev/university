{{-- Picture block. Contract: $data['image'] (Media Library id), $data['caption'], $locale --}}
@php($item = \Platform\Media\MediaItem::query()->where('is_active', true)->find($data['image'] ?? 0))
@if ($item)
    <figure class="block block--image">
        <img src="{{ $item->url('large') }}" alt="{{ $item->getTranslation('alt_text', $locale, true) }}" loading="lazy">
        @if (filled($data['caption'] ?? null))
            <figcaption>{{ $data['caption'] }}</figcaption>
        @endif
    </figure>
@endif
