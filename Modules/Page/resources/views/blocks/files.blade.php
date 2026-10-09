{{-- Downloads block. Contract: $data['heading'], $data['files'] (Media Library ids), $locale --}}
@php($ids = array_map('intval', (array) ($data['files'] ?? [])))
@php($items = \Platform\Media\MediaItem::query()->where('is_active', true)->whereIn('id', $ids)->get()->sortBy(fn ($i) => array_search($i->id, $ids, true)))
@if ($items->isNotEmpty())
    <div class="block block--files">
        @if (filled($data['heading'] ?? null))
            <h2>{{ $data['heading'] }}</h2>
        @endif
        <ul class="files__list">
            @foreach ($items as $item)
                <li><a href="{{ $item->permalink() }}" download>{{ $item->getTranslation('title', $locale, true) ?: $item->fileName() }}</a></li>
            @endforeach
        </ul>
    </div>
@endif
