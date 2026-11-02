{{-- Core rich text (core.rich-text): the editor's text in a reading column. Contract: $items (HTML, sanitised by the editor), $section --}}
<div class="container">
    <x-theme::section-head :title="$section->title" :sub="$section->subheading" />
    <div class="prose">{!! $items !!}</div>
</div>
