<?php

namespace Modules\Slider;

use Modules\Slider\Filament\SliderPlugin;
use Modules\Slider\Models\Slide;
use Modules\Slider\Sections\HeroSliderSection;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class SliderExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(SliderPlugin::class);              // Content › Slider
        $r->attachable(Slide::class);                         // "Show in" many places, main site (Admin)
        $r->homepageSection(HeroSliderSection::class);        // any homepage's hero (FR-SLD-05)
    }
}
