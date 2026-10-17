<?php

namespace Modules\Transport;

use Modules\Transport\Filament\TransportPlugin;
use Modules\Transport\Sections\TransportFinderSection;
use Modules\Transport\Settings\TransportSettings;
use Modules\Transport\Support\TransportWebsite;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 * Owner 2026-10-17: the old transport system goes — routes, stops and times are kept here.
 */
final class TransportExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(TransportPlugin::class);                                                                 // Transport › Routes, Stops, Service types
        $r->settings(TransportSettings::class);                                                                     // running service, title, semester, rules, page text, fees
        $r->websitePage('transport.index', label: 'transport::transport.title', path: '/'.TransportWebsite::PATH);  // Menus › Module page
        $r->homepageSection(TransportFinderSection::class);                                                         // stop search on the homepage
    }
}
