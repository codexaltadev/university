@if ($links = (array) settings('social.links', []))
    @include('theme::partials.social', ['links' => $links, 'class' => 'site-footer__social'])
@endif
