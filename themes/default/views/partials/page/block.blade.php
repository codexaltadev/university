{{--
    One page block (Page module, PageBlocks::WRAPPER). The page's own blocks (text, picture, accordion, files …) as DIU shows
    page text (RichHtmlContent: Bootstrap 5.0.2 in a shadow root); a module's block (admission, documents, news …) in the
    theme's own look. Contract: $type, $core (bool), $html (the block, sanitised by the kernel)
--}}
@if ($core)
    <diu-rich-html data-css="{{ theme_asset('css/bootstrap-5.0.2.min.css') }}" class="block">{!! $html !!}</diu-rich-html>
@else
    <div class="page-block page-block--{{ $type }} my-6 first:mt-0">{!! $html !!}</div>
@endif
