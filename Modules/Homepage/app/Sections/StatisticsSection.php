<?php

namespace Modules\Homepage\Sections;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;
use Platform\Statistics\Statistics;

/** Statistics counters of this homepage's owner (FR-STAT-07, FR-HOME-02). */
class StatisticsSection extends Section
{
    public static function key(): string
    {
        return 'core.statistics';
    }

    public static function label(): string
    {
        return __('homepage::homepage.sections.statistics');
    }

    public static function view(): string
    {
        return 'homepage::sections.statistics';
    }

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('set')->label(__('homepage::homepage.fields.stat_set'))->default('default')->alphaDash()->maxLength(50),
            Select::make('display')->label(__('homepage::homepage.fields.stat_layout'))
                ->options(['counter' => __('homepage::homepage.stat_layouts.counter'), 'cards' => __('homepage::homepage.stat_layouts.cards'),
                    'bar' => __('homepage::homepage.stat_layouts.bar'), 'icons' => __('homepage::homepage.stat_layouts.icons')])
                ->default('counter')->selectablePlaceholder(false),
        ];
    }

    public function items(SectionContext $ctx): array
    {
        return app(Statistics::class)->forOwner($ctx->ownerKey, (string) $ctx->setting('set', 'default'), $ctx->locale);
    }
}
