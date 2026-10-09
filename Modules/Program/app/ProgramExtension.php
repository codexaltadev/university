<?php

namespace Modules\Program;

use Modules\Department\Models\Department;
use Modules\Program\Filament\ProgramPlugin;
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
        $r->micrositeEntity(Program::class, routePrefix: 'program', parent: Department::class, home: 'program::microsite.home');   // /program/{slug}
        $r->assignable(Program::class, roles: ['coordinator', 'officer', 'contact', 'industry_expert']);
        $r->vocabulary('program.level', label: 'Program level', multiple: false);   // FR-PROG-03
        $r->vocabulary('program.tag', label: 'Program tag');                         // FR-PROG-03a
        $r->metric('program.count', ProgramCount::class);
        $r->layouts('program.listing', ['card', 'list', 'table', 'detailed']);     // FR-PROG-12
        $r->homepageSection(FeaturedProgramsSection::class);                        // FR-PROG-16
        $r->homepageSection(ProgramsByLevelSection::class);
        $r->searchable(Program::class, label: 'program::program.plural', weight: 9);
        $r->sitemap(Program::class);
    }
}
