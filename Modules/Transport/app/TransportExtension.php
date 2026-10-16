<?php

namespace Modules\Transport;

use Modules\Transport\Sections\TransportFinderSection;
use Modules\Transport\Settings\TransportSettings;
use Modules\Transport\Support\TransportWebsite;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 * No admin screens: the routes are kept in DIU's live transport system (owner 2026-10-17).
 */
final class TransportExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->settings(TransportSettings::class);                                                                     // API address, cache
        $r->websitePage('transport.index', label: 'transport::transport.title', path: '/'.TransportWebsite::PATH);  // Menus › Module page
        $r->homepageSection(TransportFinderSection::class);                                                         // stop search on the homepage
    }
}
