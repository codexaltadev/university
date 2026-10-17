{{-- Primary menu in the off-canvas drawer: every level an accordion (UI/UX Part B §4.3: mega menu → accordion) --}}
<nav class="drawer-nav">
    @include('theme::partials.menus.offcanvas-list', ['items' => $items, 'level' => 1])
</nav>
