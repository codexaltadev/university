{{-- Accordion block. Contract: $data['items'] = [['title', 'body']], $locale --}}
<div class="block block--accordion">
    @foreach ((array) ($data['items'] ?? []) as $item)
        <details class="accordion__item">
            <summary class="accordion__title">{{ $item['title'] ?? '' }}</summary>
            <div class="accordion__body prose">{{ \Modules\Page\Blocks\PageBlocks::html($item['body'] ?? null) }}</div>
        </details>
    @endforeach
</div>
