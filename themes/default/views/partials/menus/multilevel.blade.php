{{-- Primary menu "multi-level": nested fly-outs (CSS :hover / :focus-within) --}}
<nav class="nav nav--multilevel" aria-label="{{ __('theme::theme.primary_nav') }}">
    @include('theme::partials.menus.tree', ['items' => $items, 'level' => 1])
</nav>
