<?php

namespace Modules\Gallery;

use Modules\Gallery\Filament\GalleryPlugin;
use Modules\Gallery\Models\Album;
use Modules\Gallery\Models\Video;
use Modules\Gallery\Sections\PhotoGallerySection;
use Modules\Gallery\Sections\VideoGallerySection;
use Modules\Gallery\Settings\GallerySettings;
use Modules\Gallery\Tabs\EventAlbumsTab;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class GalleryExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(GalleryPlugin::class);                                                   // Content › Gallery
        $r->attachable(Album::class);                                                               // "Show in", main site (Admin), highlight
        $r->assignable(Video::class, roles: [Video::ROLE]);                                         // a lecture's speaker (FR-GAL-04)
        $r->vocabulary(Album::CATEGORY, label: 'Gallery category', hierarchical: true, multiple: false);   // FR-GAL-03
        $r->homepageSection(PhotoGallerySection::class);                                            // FR-GAL-07
        $r->homepageSection(VideoGallerySection::class);
        $r->websitePage('gallery.index', label: 'gallery::gallery.plural', path: '/'.Album::PATH);    // also /{entity}/gallery
        $r->searchable(Album::class, label: 'gallery::gallery.plural', weight: 4);
        $r->sitemap(Album::class);
        $r->menuItemType(Album::class);
        $r->layouts('gallery.album', ['masonry', 'grid', 'carousel']);                              // FR-GAL-05; first = default
        $r->layouts('gallery.listing', ['cards', 'grid']);
        $r->settings(GallerySettings::class);                                                         // the gallery's own text

        $event = 'Modules\\Events\\Models\\Event';
        if (class_exists($event)) {
            $r->entityTab($event, EventAlbumsTab::class);                                           // FR-GAL-06; Events is optional
        }
    }
}
