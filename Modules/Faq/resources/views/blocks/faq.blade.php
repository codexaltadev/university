{{-- FAQ page block (FR-FAQ-03). Contract: $data (categories, faqs, limit), $locale --}}
<div class="block block--faq">
    @include('faq::partials.accordion', ['faqs' => \Modules\Faq\Blocks\FaqBlock::faqs((array) $data)])
</div>
