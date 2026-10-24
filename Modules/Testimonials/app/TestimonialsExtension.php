<?php

namespace Modules\Testimonials;

use Modules\Testimonials\Filament\TestimonialsPlugin;
use Modules\Testimonials\Models\Testimonial;
use Modules\Testimonials\Sections\TestimonialsSection;
use Modules\Testimonials\Settings\TestimonialsSettings;
use Modules\Testimonials\Tabs\ProgramTestimonialsTab;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class TestimonialsExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(TestimonialsPlugin::class);                                                  // Content › Testimonials
        $r->attachable(Testimonial::class);                                                             // "Show in", main site (Admin)
        $r->assignable(Testimonial::class, roles: [Testimonial::ROLE]);                                 // the person (FR-TEST-01)
        $r->homepageSection(TestimonialsSection::class);                                                // FR-TEST-03
        $r->websitePage('testimonials.index', label: 'testimonials::testimonials.plural', path: '/'.Testimonial::PATH);
        $r->layouts('testimonials.listing', ['slider', 'grid', 'masonry', 'video']);                    // FR-TEST-02; first = default
        $r->settings(TestimonialsSettings::class);                                                         // the page's own text

        $program = 'Modules\\Program\\Models\\Program';
        if (class_exists($program)) {
            $r->entityTab($program, ProgramTestimonialsTab::class);                                     // on a program's page; Program is optional
        }
    }
}
