{{-- DIU /gallery (and an entity's): see partials/gallery/page. Contract: the module's (albums, categories, category, kind, layout, base), $page (GalleryPage) --}}
<x-theme::layout :title="$page->heading() ?? (settings('gallery.title') ?: __('gallery::gallery.plural'))">
    @include('theme::partials.gallery.page')
</x-theme::layout>
