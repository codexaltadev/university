<?php

namespace Modules\Announcement;

use Modules\Announcement\Filament\AnnouncementPlugin;
use Modules\Announcement\Models\Widget;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 * The website part is the layout slots announcement / ticker / popup (SiteData, provider).
 */
final class AnnouncementExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(AnnouncementPlugin::class);   // Content › Announcements
        $r->attachable(Widget::class);                   // "Show in", main site (Admin) — FR-ANW-02
    }
}
