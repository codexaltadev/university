{{-- Footer logo: the footer logo on the dark band as it is; the normal logo on a light chip so it stays visible --}}
<a href="{{ url('/') }}" @class(['site-footer__logo', 'site-footer__logo--chip' => ! settings()->media('branding.logo_footer')])>
    <x-platform::logo variant="footer" class="site-logo" />
</a>
