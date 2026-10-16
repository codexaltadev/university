<?php

namespace Platform\Website;

use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Platform\Context\SiteContext;

/**
 * A block another module offers for the footer columns of Header & Footer (FR-HF-03,
 * FR-LINK-02): "Important Links", later "Latest notices" … The footer editor lists it with the
 * built-in blocks; a theme may override the look (views/partials/footer-blocks/{key}.blade.php),
 * else the module's own view is used. An inactive module's blocks are simply not shown.
 *
 *     $r->footerBlock(LinksFooterBlock::class);   // provides "footer_blocks"
 */
abstract class FooterBlock
{
    /** Stored as the block's "type"; never change it once footers use it. "links.footer" */
    abstract public static function key(): string;

    abstract public static function label(): string;

    /** Rendered with $block (its saved fields), $context and data(). */
    abstract public static function view(): string;

    /**
     * The block's own fields in the footer editor, saved inside the block (e.g. which group).
     *
     * @return array<Component|Field>
     */
    public static function schema(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>
     */
    public function data(array $block, SiteContext $context): array
    {
        return [];
    }
}
