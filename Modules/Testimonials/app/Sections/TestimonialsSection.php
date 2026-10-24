<?php

namespace Modules\Testimonials\Sections;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Testimonials\Filament\Resources\Testimonials\TestimonialResource;
use Modules\Testimonials\Models\Testimonial;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;
use Platform\Relations\RelationEngine;

/**
 * Homepage section "What People Say" (FR-TEST-03): the place's highlighted testimonials — only those
 * unless the builder asks for more (owner 2026-10-24) — of the chosen types when set; "latest" means
 * the dragged order (FR-TEST-04), testimonials have no date. Slider or grid.
 */
class TestimonialsSection extends Section
{
    public static function key(): string
    {
        return 'testimonials.list';
    }

    public static function label(): string
    {
        return __('testimonials::testimonials.section.label');
    }

    public static function view(): string
    {
        return 'testimonials::sections.list';
    }

    public static function model(): ?string
    {
        return Testimonial::class;
    }

    /** Owner 2026-10-24: a homepage shows only the testimonials chosen for it (Highlight), not the rest */
    public static function defaultSourceMode(): string
    {
        return 'highlighted';
    }

    public static function settingsSchema(): array
    {
        return [
            Select::make('types')->label(__('testimonials::testimonials.section.types'))->helperText(__('testimonials::testimonials.section.types_help'))
                ->options(TestimonialResource::typeOptions())->multiple(),
            Radio::make('display')->label(__('testimonials::testimonials.section.layout'))->inline()->default('slider')
                ->options(['slider' => __('testimonials::testimonials.layouts.slider'), 'grid' => __('testimonials::testimonials.layouts.grid')]),
        ];
    }

    /** @return Collection<int, Testimonial> */
    public function items(SectionContext $ctx): Collection
    {
        $mode = $ctx->setting('source_mode', static::defaultSourceMode());
        if ($mode === 'manual') {
            return $ctx->applySourceMode(Testimonial::class, 6, static::defaultSourceMode());
        }

        $count = max(1, (int) $ctx->setting('count', 6));
        $types = array_values(array_intersect(Testimonial::TYPES, (array) $ctx->setting('types')));
        $content = fn () => app(RelationEngine::class)->for($ctx->ownerKey)->content(Testimonial::class)
            ->includeDescendants((bool) $ctx->setting('include_descendants', false));
        $ofType = fn (Testimonial $t): bool => $types === [] || in_array($t->testimonial_type, $types, true);

        $highlighted = $mode === 'latest' ? collect() : $content()->highlighted(100)->filter($ofType)->take($count)->values();
        if ($mode === 'highlighted' || $highlighted->count() >= $count) {
            return (new Testimonial)->newCollection($highlighted->all());
        }

        $rest = $content()->query()->reorder()->ordered()
            ->when($types !== [], fn ($query) => $query->whereIn('testimonial_type', $types))
            ->whereKeyNot($highlighted->map(fn (Model $t) => $t->getKey())->all())
            ->limit($count - $highlighted->count())->get();

        return (new Testimonial)->newCollection($highlighted->concat($rest)->values()->all());
    }
}
