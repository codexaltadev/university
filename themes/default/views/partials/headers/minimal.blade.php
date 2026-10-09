{{-- Header "minimal": logo and a one-level menu --}}
<header class="site-header site-header--minimal">
    <div class="container site-header__inner">
        <a href="{{ url('/') }}" class="site-header__brand"><x-platform::logo variant="light" class="site-logo" /></a>
        <x-platform::menu location="primary" :depth="1" view="theme::partials.menus.inline" />
    </div>
</header>
