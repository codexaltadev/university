{{-- Text block. Contract: $data['body'] (rich text), $locale --}}
<div class="block block--text prose">{{ \Modules\Page\Blocks\PageBlocks::html($data['body'] ?? null) }}</div>
