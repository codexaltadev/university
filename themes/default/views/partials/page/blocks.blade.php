{{-- A Page's blocks in a page template: each already in partials/page/block (page text as DIU's article, a module's block in the theme's look). Contract: $body (rendered blocks, sanitised by the kernel) --}}
<div class="page-blocks">{!! $body !!}</div>
