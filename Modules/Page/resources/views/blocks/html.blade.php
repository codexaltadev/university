{{-- HTML block (as written, cleaned). Contract: $data['html'], $locale --}}
<div class="block block--html">{{ \Modules\Page\Blocks\HtmlBlock::clean($data['html'] ?? null) }}</div>
