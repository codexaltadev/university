<?php

namespace Modules\International\Support;

use Illuminate\Database\Eloquent\Model;
use Modules\International\Models\Visitor;
use Platform\Assignments\ManualPerson;
use Platform\Modules\ModuleRegistry;
use Platform\Relations\RelationEngine;

/**
 * A visitor's words shown as a testimonial too (owner 2026-10-24): visitors and testimonials stay
 * two things — a guest who came to DIU, and anyone's experience of it — but a visitor's comment
 * may also stand among the testimonials. Made once, linked by source_key: the writer becomes a
 * manual person (name, designation, organisation), type "visitor", the visitor's country and
 * photo, shown where the visitor is shown. Needs the Testimonials module.
 */
class VisitorTestimonial
{
    public const TESTIMONIAL = 'Modules\\Testimonials\\Models\\Testimonial';

    public const AUTHORS = 'Modules\\Testimonials\\Support\\Authors';

    public static function available(): bool
    {
        return class_exists(self::TESTIMONIAL) && app(ModuleRegistry::class)->isActive('testimonials');
    }

    public static function sourceKey(Visitor $visitor): string
    {
        return $visitor->getMorphClass().':'.$visitor->getKey();
    }

    /** The testimonial made from this visitor, if any */
    public static function of(Visitor $visitor): ?Model
    {
        return self::available() ? (self::TESTIMONIAL)::query()->withoutGlobalScopes()->where('source_key', self::sourceKey($visitor))->first() : null;
    }

    /** Make it (or give the one already made) */
    public static function make(Visitor $visitor): ?Model
    {
        if (! self::available()) {
            return null;
        }
        if ($existing = self::of($visitor)) {
            return $existing;
        }

        $quote = collect($visitor->getTranslations('body'))->map(fn ($html): string => trim(html_entity_decode(strip_tags((string) $html))))->filter()->all();
        $testimonial = (self::TESTIMONIAL)::query()->create([
            'quote' => $quote ?: ['en' => (string) $visitor->name],
            'testimonial_type' => 'visitor',
            'country' => $visitor->country?->iso2,
            'source_key' => self::sourceKey($visitor),
            'is_active' => (bool) $visitor->is_active,
        ]);

        $person = ManualPerson::query()->create([
            'name' => $visitor->getTranslations('name'),
            'designation' => $visitor->getTranslations('designation'),
            'organization' => $visitor->getTranslations('organization'),
            'is_active' => true,
        ]);
        $title = collect(array_keys([...$visitor->getTranslations('designation'), ...$visitor->getTranslations('organization')]))
            ->mapWithKeys(fn (string $l): array => [$l => collect([$visitor->getTranslation('designation', $l, false), $visitor->getTranslation('organization', $l, false)])->filter()->implode(', ')])
            ->filter()->all();
        app(self::AUTHORS)->set($testimonial, $person, $title ?: null);

        if ($photo = $visitor->firstMedia('photo')) {
            $testimonial->syncMedia('photo', [$photo->id]);   // the same Media Library item, no copy
        }

        $engine = app(RelationEngine::class);
        $engine->sync($testimonial, $engine->targetsOf($visitor), mainSite: $engine->isOnMainSite($visitor), user: null);

        return $testimonial;
    }
}
