<?php

namespace Modules\Lab;

use Modules\Lab\Filament\LabPlugin;
use Modules\Lab\Metrics\LabCount;
use Modules\Lab\Metrics\LabEquipmentCount;
use Modules\Lab\Metrics\LabProductCount;
use Modules\Lab\Metrics\LabProjectCount;
use Modules\Lab\Models\Lab;
use Modules\Lab\Models\LabItem;
use Modules\Lab\Sections\LabsSection;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class LabExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $department = class_exists(Lab::DEPARTMENT) ? Lab::DEPARTMENT : null;   // Department is optional

        $r->filamentPlugin(LabPlugin::class);                                                                       // Services › Labs
        $r->relationTarget(Lab::class, parent: $department, parentKey: $department ? 'department_id' : null);      // FR-LAB-02, FR-LAB-07
        $r->micrositeEntity(Lab::class, routePrefix: 'lab', parent: $department, home: 'lab::microsite.home', childSegment: 'lab');   // /fsit/cse/lab/{slug} or /lab/{slug} (FR-LAB-06)
        $r->assignable(Lab::class, roles: ['in_charge', 'team']);                                                   // Person Picker
        $r->dynamicPermissions(Lab::class);                                                                         // "Manage:Lab#{id}" (FR-LAB-03)
        $r->searchable(Lab::class, label: 'lab::lab.plural', weight: 6);
        $r->vocabulary(Lab::TYPE, label: 'Lab type', multiple: false);                                             // FR-LAB-01
        foreach (LabItem::TYPES as $type) {
            $r->vocabulary(LabItem::vocabulary($type), label: 'Lab '.$type.' category', multiple: false);           // FR-LAB-04: shared by all labs
        }
        $r->metric('lab.count', LabCount::class);
        $r->metric('lab.equipment', LabEquipmentCount::class);                                                      // FR-LAB-11
        $r->metric('lab.projects', LabProjectCount::class);
        $r->metric('lab.products', LabProductCount::class);
        $r->layouts('lab.listing', ['grid', 'list', 'cards']);                                                      // FR-LAB-05
        $r->websitePage('lab.index', label: 'lab::lab.plural', path: '/'.Lab::LISTING);
        $r->homepageSection(LabsSection::class);                                                    // a place's labs (Phase 4.4b)
    }
}
