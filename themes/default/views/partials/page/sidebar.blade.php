{{--
    A page's sidebar, left or right (templates with-sidebar / right-sidebar): the "sidebar" slot, "Browse by Section" (the
    page's sidebar menu) and its Quick Links. Contract: $page (quick_links)
--}}
<div class="lg:sticky lg:top-32 space-y-6">
    <x-platform::slot name="sidebar" />
    <x-platform::menu location="sidebar" :depth="2" view="theme::partials.menus.browse" />
    @include('theme::partials.diu.quick-links', ['links' => link_list($page->quick_links)])
</div>
