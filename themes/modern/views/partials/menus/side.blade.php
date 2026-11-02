{{-- Sidebar menu: a card with the links, the current page marked. Contract: $items --}}
<nav class="card aside-card side-menu">
    @include('theme::partials.menus.dropdown', ['items' => $items])
</nav>
