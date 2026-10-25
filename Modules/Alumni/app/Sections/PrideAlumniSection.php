<?php

namespace Modules\Alumni\Sections;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Collection;
use Modules\Alumni\Models\Alumnus;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;

/**
 * Homepage section "Our Pride Alumni" (FR-ALUM-04): Pride alumni in order, as a slider or a
 * grid; on a Department / Program microsite only its own.
 */
class PrideAlumniSection extends Section
{
    public static function key(): string
    {
        return 'alumni.pride';
    }

    public static function label(): string
    {
        return __('alumni::alumni.pride');
    }

    public static function view(): string
    {
        return 'alumni::sections.pride';
    }

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('count')->label(__('alumni::alumni.section.count'))->integer()->minValue(1)->maxValue(24)->default(8),
            Radio::make('display')->label(__('alumni::alumni.section.display'))->inline()->default('slider')
                ->options(['slider' => __('alumni::alumni.section.slider'), 'grid' => __('alumni::alumni.section.grid')]),
        ];
    }

    /** @return Collection<int, Alumnus> */
    public function items(SectionContext $ctx): Collection
    {
        $owner = Alumnus::ownerOf($ctx->site()->entity);   // a Faculty's departments, a Program's (or its department's) too
        if ($owner === null) {
            return collect();
        }

        return Alumnus::query()->frontendVisible()->pride()->ownedBy($owner)->ordered()->limit((int) ($ctx->setting('count') ?: 8))->get();
    }
}
