<?php

namespace Modules\Homepage\Sections;

use Filament\Forms\Components\Select;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;

/** Empty space between sections (FR-HOME-02). */
class SpacerSection extends Section
{
    public static function key(): string
    {
        return 'core.spacer';
    }

    public static function label(): string
    {
        return __('homepage::homepage.sections.spacer');
    }

    public static function view(): string
    {
        return 'homepage::sections.spacer';
    }

    public static function settingsSchema(): array
    {
        return [
            Select::make('size')->label(__('homepage::homepage.fields.size'))
                ->options(['sm' => 'S', 'md' => 'M', 'lg' => 'L', 'xl' => 'XL'])->default('md')->selectablePlaceholder(false),
        ];
    }

    public function items(SectionContext $ctx): string
    {
        return (string) $ctx->setting('size', 'md');
    }
}
