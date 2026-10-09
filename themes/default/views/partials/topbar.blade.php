{{-- Top bar (FR-HF-02). $config: the region's switches; empty = theme default --}}
<div class="topbar">
    <div class="container topbar__inner">
        <div class="topbar__contact">
            @if (($config['show_phone'] ?? true) && settings('contact.phone'))
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', settings('contact.phone')) }}">{{ settings('contact.phone') }}</a>
            @endif
            @if (($config['show_email'] ?? true) && settings('contact.email'))
                <a href="mailto:{{ settings('contact.email') }}">{{ settings('contact.email') }}</a>
            @endif
        </div>
        @if ($config['show_quick_links'] ?? true)
            <x-platform::menu location="topbar" :depth="1" view="theme::partials.menus.inline" />
        @endif
    </div>
</div>
