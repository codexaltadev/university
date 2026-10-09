{{-- Tabs block. Contract: $data['tabs'] = [['label', 'body']], $locale. Without JavaScript all panels show. --}}
@php($tabs = array_values((array) ($data['tabs'] ?? [])))
<div class="block block--tabs" x-data="{ tab: 0 }">
    <div role="tablist" class="tabs__list">
        @foreach ($tabs as $i => $tab)
            <button type="button" role="tab" class="tabs__tab" :aria-selected="tab === {{ $i }}" @click="tab = {{ $i }}">{{ $tab['label'] ?? '' }}</button>
        @endforeach
    </div>
    @foreach ($tabs as $i => $tab)
        <div role="tabpanel" class="tabs__panel prose" x-show="tab === {{ $i }}">{{ \Modules\Page\Blocks\PageBlocks::html($tab['body'] ?? null) }}</div>
    @endforeach
</div>
