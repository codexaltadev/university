{{-- A section without its own view: its items as a plain list --}}
@if (is_iterable($items) && collect($items)->isNotEmpty())
    <ul>
        @foreach ($items as $item)
            <li>{{ is_object($item) ? ($item->title ?? $item->name ?? $item->question ?? '') : (is_array($item) ? ($item['title'] ?? $item['label'] ?? '') : $item) }}</li>
        @endforeach
    </ul>
@endif
