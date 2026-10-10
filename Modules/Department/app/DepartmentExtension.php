<?php

namespace Modules\Department;

use Modules\Department\Filament\DepartmentPlugin;
use Modules\Department\Filament\Parts\FacultyDepartmentsPart;
use Modules\Department\Metrics\DepartmentCount;
use Modules\Department\Models\Department;
use Modules\Department\Sections\DepartmentsSection;
use Modules\Faculty\Models\Faculty;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class DepartmentExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->websitePage('department.index', label: 'department::department.plural', path: '/departments');   // Menus › Module page
        $r->filamentPlugin(DepartmentPlugin::class);
        $r->relationTarget(Department::class, parent: Faculty::class, parentKey: 'faculty_id');   // FR-DEPT-02
        $r->scopeable(Department::class);                         // Department users; Faculty users through the hierarchy
        $r->micrositeEntity(Department::class, routePrefix: 'department', parent: Faculty::class, home: 'department::microsite.home');   // /{faculty}/{slug}; DIU's /department/{slug} redirects (FR-GEN-34)
        $r->assignable(Department::class, roles: ['head', 'coordinator', 'officer', 'contact', 'industry_expert']);                     // FR-DEPT-03
        $r->metric('department.count', DepartmentCount::class);
        $r->layouts('department.listing', ['grid', 'list', 'card']);   // FR-DEPT-09
        $r->homepageSection(DepartmentsSection::class);                 // FR-DEPT-09, FR-FAC-06
        $r->searchable(Department::class, label: 'department::department.plural', weight: 7);
        $r->sitemap(Department::class);
        $r->entityFormPart(Faculty::class, FacultyDepartmentsPart::class);   // the Faculty editor's "Departments" (read only)
    }
}
