{{-- One page block (Page module, PageBlocks::WRAPPER): the page's own blocks as rich text, a module's block as it is. Contract: $type, $core, $html --}}
<div @class(['page-block', 'page-block--'.$type, 'prose' => $core, 'my-8 first:mt-0'])>{!! $html !!}</div>
