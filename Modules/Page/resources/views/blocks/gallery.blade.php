{{-- Gallery block. Contract: $data['images'] (Media Library ids, in order), $data['columns'], $locale --}}
@php($ids = array_map('intval', (array) ($data['images'] ?? [])))
@php($items = \Platform\Media\MediaItem::query()->where('is_active', true)->whereIn('id', $ids)->get()->sortBy(fn ($i) => array_search($i->id, $ids, true)))
@if ($items->isNotEmpty())
    <div class="block block--gallery" style="display:grid;gap:var(--space-3, 12px);grid-template-columns:repeat({{ (int) ($data['columns'] ?? 3) }}, minmax(0, 1fr))">
        @foreach ($items as $item)
            <a href="{{ $item->url('large') }}" class="block--gallery__item">
                <img src="{{ $item->url('medium') }}" alt="{{ $item->getTranslation('alt_text', $locale, true) }}" loading="lazy">
            </a>
        @endforeach
    </div>
@endif
