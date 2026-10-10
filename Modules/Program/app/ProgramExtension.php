<?php

namespace Modules\Program;

use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\Program\Filament\Parts\DepartmentProgramsPart;
use Modules\Program\Filament\Parts\FacultyProgramsPart;
use Modules\Program\Filament\ProgramPlugin;
use Modules\Program\Metrics\IndustryExpertCount;
use Modules\Program\Metrics\MajorCount;
use Modules\Program\Metrics\ProgramCount;
use Modules\Program\Models\Program;
use Modules\Program\Sections\FeaturedProgramsSection;
use Modules\Program\Sections\ProgramsByLevelSection;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class ProgramExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(ProgramPlugin::class);
        $r->relationTarget(Program::class, parent: Department::class, parentKey: 'department_id');   // FR-PROG-02
        $r->scopeable(Program::class);                            // Program users; Faculty / Department users through the hierarchy
        $r->micrositeEntity(Program::class, routePrefix: 'program', parent: Department::class, home: 'program::microsite.home');   // /{faculty}/{department}/{slug} (FR-GEN-34)
        $r->assignable(Program::class, roles: ['coordinator', 'officer', 'contact', 'industry_expert']);
        $r->vocabulary('program.level', label: 'Program level', multiple: false);   // FR-PROG-03
        $r->vocabulary('program.tag', label: 'Program tag');                         // FR-PROG-03a
        $r->vocabulary('program.major-category', label: 'Major category', multiple: false);   // FR-PROG-04
        $r->metric('program.count', ProgramCount::class);
        $r->metric('program.majors', MajorCount::class);                   // FR-PROG-11
        $r->metric('program.industry-experts', IndustryExpertCount::class);
        $r->layouts('program.listing', ['card', 'list', 'table', 'detailed']);     // FR-PROG-12
        $r->homepageSection(FeaturedProgramsSection::class);                        // FR-PROG-16
        $r->homepageSection(ProgramsByLevelSection::class);
        $r->searchable(Program::class, label: 'program::program.plural', weight: 9);
        $r->sitemap(Program::class);
        $r->entityFormPart(Department::class, DepartmentProgramsPart::class);   // editors' "Programs" (read only)
        $r->entityFormPart(Faculty::class, FacultyProgramsPart::class);
    }
}
