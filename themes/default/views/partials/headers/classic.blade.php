{{-- Header "classic": logo left, menu and search right (FR-HF-01, FR-SRCH-05) --}}
<header @class(['site-header', 'site-header--classic', 'is-sticky' => $config['sticky'] ?? theme_option('sticky_header')])>
    <div class="container site-header__inner">
        <a href="{{ url('/') }}" class="site-header__brand"><x-platform::logo variant="light" class="site-logo" /></a>
        <x-platform::menu location="primary" :depth="3" />
        @if ($config['show_search'] ?? true)
            <livewire:platform.instant-search :scope="$context->isMicrosite() ? $context->key() : null" />
        @endif
    </div>
</header>
