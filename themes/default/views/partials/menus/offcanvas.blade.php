{{-- Primary menu in the mobile menu: every level an accordion (UI/UX Part B §4.3) --}}
<nav class="drawer-nav" aria-label="{{ __('theme::theme.primary_nav') }}">
    @include('theme::partials.menus.offcanvas-list', ['items' => $items, 'level' => 1])
</nav>
