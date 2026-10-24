<?php

namespace Platform\Homepage;

use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * A kind of homepage section (C-10, Module Guide §11.1): the platform's own (hero,
 * statistics …) and the ones modules register with $r->homepageSection(Latest News::class)
 * ("homepage_sections" in provides). The theme renders view() with the section's data.
 *
 *     class FaqHighlightsSection extends Section
 *     {
 *         public static function key(): string { return 'faq.highlights'; }
 *         public static function label(): string { return __('faq::faq.section_label'); }
 *         public static function view(): string { return 'faq::sections.faq-highlights'; }
 *         public static function model(): ?string { return Faq::class; }   // content section: source modes
 *
 *         public function items(SectionContext $ctx): Collection
 *         {
 *             return $ctx->applySourceMode(Faq::class, defaultCount: 5);
 *         }
 *     }
 */
abstract class Section
{
    /** "{module}.{name}", stored on the section; never change it. */
    abstract public static function key(): string;

    abstract public static function view(): string;

    public static function label(): string
    {
        return Str::headline(Str::after(static::key(), '.'));
    }

    /**
     * Content model of a content section (Latest News …): the builder offers the
     * source modes (FR-HOME-11), item count and manual pick. Null for fixed sections.
     *
     * @return class-string|null
     */
    public static function model(): ?string
    {
        return null;
    }

    /**
     * The source mode a content section starts with in the builder (one of SectionContext::MODES):
     * "highlighted_first" fills up with the rest; a section that should show only what was chosen
     * (testimonials) says "highlighted".
     */
    public static function defaultSourceMode(): string
    {
        return 'highlighted_first';
    }

    /**
     * Extra settings fields of this section, stored under "settings".
     *
     * @return array<Component|Field>
     */
    public static function settingsSchema(): array
    {
        return [];
    }

    /** Data for the view (items, set, text …). */
    public function items(SectionContext $ctx): mixed
    {
        return null;
    }
}
