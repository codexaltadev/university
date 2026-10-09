<?php

namespace Platform\Pages;

use BackedEnum;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Str;

/**
 * One kind of page content block (FR-PAGE-02): text, picture, accordion … A module
 * adds its own with $r->pageBlock(ProgramListBlock::class) ("page_blocks" in provides).
 * Pictures and files inside a block are Media Library ids; list them in mediaIds()
 * so they count as usage.
 */
abstract class PageBlock
{
    /** Stored as the block's "type"; never change it once pages use it. */
    abstract public static function name(): string;

    /** @return array<Component|Field> */
    abstract public static function schema(): array;

    public static function label(): string
    {
        return Str::headline(static::name());
    }

    public static function icon(): string|BackedEnum|null
    {
        return null;
    }

    /** @param array<string, mixed> $data the block's data @return list<int> */
    public static function mediaIds(array $data): array
    {
        return [];
    }

    /**
     * Website view of the block; gets $data (the block's fields) and $locale. The
     * active theme may override it (views/modules/{module}/blocks/{name}.blade.php).
     */
    public static function view(): string
    {
        return 'page::blocks.'.static::name();
    }

    public static function make(): Block
    {
        return Block::make(static::name())->label(static::label())->icon(static::icon())->schema(static::schema());
    }
}
