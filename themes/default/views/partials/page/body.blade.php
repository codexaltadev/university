{{-- A page's blocks as DIU shows page text (RichHtmlContent: Bootstrap 5.0.2 in a shadow root). Contract: $body (rendered blocks, sanitised by the kernel) --}}
<diu-rich-html data-css="{{ theme_asset('css/bootstrap-5.0.2.min.css') }}" class="block">{!! $body !!}</diu-rich-html>
