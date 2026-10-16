<?php

namespace Modules\Documents;

use Modules\Documents\Blocks\DocumentBlock;
use Modules\Documents\Filament\DocumentsPlugin;
use Modules\Documents\Models\Document;
use Modules\Documents\Sections\DownloadsSection;
use Modules\Documents\Sections\ProspectusSection;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class DocumentsExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(DocumentsPlugin::class);                                                   // Content › Documents
        $r->attachable(Document::class);                                                              // "Show in", main site (Admin), highlight
        $r->vocabulary(Document::CATEGORY, label: 'Document category', hierarchical: true, multiple: false);
        $r->homepageSection(DownloadsSection::class);                                                 // FR-DOC-08
        $r->homepageSection(ProspectusSection::class);
        $r->pageBlock(DocumentBlock::class);                                                          // a document or its flipbook on any page
        $r->websitePage('documents.index', label: 'documents::documents.plural', path: '/'.Document::PATH);
        $r->searchable(Document::class, label: 'documents::documents.plural', weight: 4);
        $r->sitemap(Document::class);
        $r->menuItemType(Document::class);
        $r->layouts('documents.listing', ['table', 'cards', 'list']);                                 // FR-DOC-04; first = default
    }
}
