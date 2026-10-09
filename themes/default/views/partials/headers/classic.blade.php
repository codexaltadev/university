{{-- Header "classic": logo left, menu right (FR-HF-01) --}}
<header @class(['site-header', 'site-header--classic', 'is-sticky' => $config['sticky'] ?? theme_option('sticky_header')])>
    <div class="container site-header__inner">
        <a href="{{ url('/') }}" class="site-header__brand"><x-platform::logo variant="light" class="site-logo" /></a>
        <x-platform::menu location="primary" :depth="3" />
    </div>
</header>
