<?php

namespace Modules\Slider\Sections;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Collection;
use Modules\Slider\Models\Slide;
use Modules\Slider\Support\Hero;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;

/**
 * Homepage section "Hero" (FR-SLD-03/05/06): the slides shown in this homepage's place — one is a
 * still hero, several a slider, a slide with a video a full-screen background video; none → the
 * place's banner, then a parent's. Autoplay, interval, transition, arrows, dots, height.
 */
class HeroSliderSection extends Section
{
    public static function key(): string
    {
        return 'slider.hero';
    }

    public static function label(): string
    {
        return __('slider::slider.section.label');
    }

    public static function view(): string
    {
        return 'slider::sections.hero';
    }

    public static function settingsSchema(): array
    {
        return [
            Grid::make(3)->schema([
                Toggle::make('autoplay')->label(__('slider::slider.section.autoplay'))->default(true)->inline(false),
                TextInput::make('interval')->label(__('slider::slider.section.interval'))->integer()->minValue(3)->maxValue(30)->default(6)->suffix('s'),
                Select::make('transition')->label(__('slider::slider.section.transition'))->default('slide')->selectablePlaceholder(false)
                    ->options(['slide' => __('slider::slider.section.slide'), 'fade' => __('slider::slider.section.fade')]),
                Toggle::make('arrows')->label(__('slider::slider.section.arrows'))->default(true)->inline(false),
                Toggle::make('dots')->label(__('slider::slider.section.dots'))->default(true)->inline(false),
                Select::make('height')->label(__('slider::slider.section.height'))->default('large')->selectablePlaceholder(false)
                    ->options(['screen' => __('slider::slider.heights.screen'), 'large' => __('slider::slider.heights.large'), 'medium' => __('slider::slider.heights.medium')]),
            ]),
        ];
    }

    /** @return Collection<int, Slide> */
    public function items(SectionContext $ctx): Collection
    {
        return app(Hero::class)->for($ctx->ownerKey);
    }
}
