<?php

namespace Modules\Testimonials\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Modules\Testimonials\Models\Testimonial;
use Platform\Context\MicrositeResolver;
use Platform\Context\SiteContext;
use Platform\Relations\RelationEngine;
use Platform\Seo\Seo;
use Platform\Theme\LayoutResolver;

/**
 * /testimonials and /{entity}/testimonials (FR-TEST-02): the place's testimonials in their
 * dragged order; ?type=student|alumni|parent|employer|partner; slider / grid / masonry / video
 * (video: only those with a video).
 */
class TestimonialsWebsite
{
    public const VIEWS = ['slider', 'grid', 'masonry', 'video'];

    public function respond(string $rest, SiteContext $context, string $locale): ?Response
    {
        if (trim($rest, '/') !== Testimonial::PATH) {
            return null;
        }

        $shown = self::shown($context->isMicrosite() ? $context->key() : RelationEngine::SITE_KEY, $context->isMicrosite());
        if ($context->isMicrosite() && ! (clone $shown)->exists()) {
            return null;
        }
        $base = $context->isMicrosite() ? rtrim((string) app(MicrositeResolver::class)->urlFor($context->entity, $locale), '/') : '';
        app(Seo::class)->set(['title' => __('testimonials::testimonials.plural'), 'path' => $base.'/'.Testimonial::PATH]);

        $request = request();
        $type = in_array($request->query('type'), Testimonial::TYPES, true) ? (string) $request->query('type') : null;
        $view = $request->query('view');
        $layout = is_string($view) && in_array($view, self::VIEWS, true) ? $view : (app(LayoutResolver::class)->for('testimonials.listing') ?? 'slider');
        $layout = in_array($layout, self::VIEWS, true) ? $layout : 'slider';

        $items = (clone $shown)->when($type, fn (Builder $q) => $q->where('testimonial_type', $type))
            ->when($layout === 'video', fn (Builder $q) => $q->whereNotNull('video_url'))->get();
        $types = (clone $shown)->reorder()->distinct()->pluck('testimonial_type')->all();

        return response()->view('testimonials::index', ['items' => $items, 'type' => $type, 'types' => array_values(array_intersect(Testimonial::TYPES, $types)), 'layout' => $layout, 'base' => $base]);
    }

    /** Visible testimonials of a place in their order (with the entities below it when asked). */
    public static function shown(string $contextKey, bool $descendants = false): Builder
    {
        return app(RelationEngine::class)->for($contextKey)->content(Testimonial::class)->includeDescendants($descendants)->query()->reorder()->ordered();
    }
}
