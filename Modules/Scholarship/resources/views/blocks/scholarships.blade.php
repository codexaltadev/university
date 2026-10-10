{{-- Scholarships page block. Contract: $data (categories, scholarships, layout, limit), $locale --}}
@php($items = \Modules\Scholarship\Blocks\ScholarshipsBlock::scholarships((array) $data))
<div class="block block--scholarships">
    @include('scholarship::partials.'.(($data['layout'] ?? 'cards') === 'accordion' ? 'accordion' : 'cards'), ['scholarships' => $items])
</div>
