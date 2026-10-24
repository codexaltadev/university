{{-- DIU gallery album (a DIU sub-category): see partials/gallery/page. Contract: the module's (album, photos, uploads, videos, layout, base), $page (GalleryPage) --}}
<x-theme::layout :title="$album->title">
    @include('theme::partials.gallery.page', ['videos' => $videos, 'uploads' => $uploads])
</x-theme::layout>
