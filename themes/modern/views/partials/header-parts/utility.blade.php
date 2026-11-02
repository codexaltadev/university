{{-- Utility row: the "topbar" menu and the languages; nothing when both are empty --}}
<div class="site-utility no-print">
    <div class="container site-utility__inner">
        <x-platform::menu location="topbar" :depth="1" view="theme::partials.menus.inline" />
        <x-platform::languages class="languages" />
    </div>
</div>
