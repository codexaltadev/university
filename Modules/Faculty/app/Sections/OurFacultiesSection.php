<?php

namespace Modules\Faculty\Sections;

use Filament\Forms\Components\TextInput;
use Illuminate\Support\Collection;
use Modules\Faculty\Models\Faculty;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;

/** Homepage section "Our Faculties" (FR-FAC-08): the listed faculties in order. */
class OurFacultiesSection extends Section
{
    public static function key(): string
    {
        return 'faculty.list';
    }

    public static function label(): string
    {
        return __('faculty::faculty.section_label');
    }

    public static function view(): string
    {
        return 'faculty::sections.our-faculties';
    }

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('limit')->label(__('faculty::faculty.fields.section_limit'))->numeric()->minValue(1)->maxValue(24)->placeholder('12'),
        ];
    }

    public function items(SectionContext $ctx): Collection
    {
        return Faculty::query()->listed()->limit((int) ($ctx->setting('limit') ?: 12))->get();
    }
}
