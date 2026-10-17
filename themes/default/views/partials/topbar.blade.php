{{-- Top bar (FR-HF-02). $config: the region's switches; empty = theme default. On a microsite the left side leads back to the main site --}}
<div class="topbar">
    <div class="container topbar__inner">
        <div class="topbar__start">
            @if ($context->isMicrosite())
                <a href="{{ url('/') }}" class="topbar__back"><x-theme::icon name="arrow-left" width="14" height="14" />{{ settings('general.site_name') }}</a>
            @else
                @if (($config['show_phone'] ?? true) && settings('contact.phone'))
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', settings('contact.phone')) }}"><x-theme::icon name="phone" width="14" height="14" />{{ settings('contact.phone') }}</a>
                @endif
                @if (($config['show_email'] ?? true) && settings('contact.email'))
                    <a href="mailto:{{ settings('contact.email') }}"><x-theme::icon name="mail" width="14" height="14" />{{ settings('contact.email') }}</a>
                @endif
            @endif
        </div>
        <div class="topbar__end">
            @if ($config['show_quick_links'] ?? true)
                <x-platform::menu location="topbar" :depth="1" view="theme::partials.menus.inline" />
            @endif
            @if (($config['show_social'] ?? false) && ($social = (array) settings('social.links', [])))
                @include('theme::partials.social', ['links' => $social, 'class' => 'topbar__social'])
            @endif
            @if ($config['show_language_switcher'] ?? true)
                <x-platform::languages class="topbar__languages" />
            @endif
            @if ($config['show_login'] ?? false)
                <a href="{{ url('/admin/login') }}" class="topbar__login"><x-theme::icon name="user" width="14" height="14" />{{ __('theme::theme.login') }}</a>
            @endif
        </div>
    </div>
</div>
