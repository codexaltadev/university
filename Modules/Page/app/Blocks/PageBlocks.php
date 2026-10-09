<?php

namespace Modules\Page\Blocks;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\RichEditor;
use Platform\Pages\PageBlock;
use Platform\Registry\Registry;

/**
 * The page block catalogue (FR-PAGE-01/02): core blocks of this module plus the
 * blocks active modules registered with $r->pageBlock(). People card comes with the
 * Person Picker (People modules).
 */
class PageBlocks
{
    public const CORE = [
        TextBlock::class, ImageBlock::class, GalleryBlock::class, AccordionBlock::class,
        TabsBlock::class, FilesBlock::class, EmbedBlock::class, ButtonBlock::class,
    ];

    /** @return array<string, class-string<PageBlock>> name => block */
    public static function all(): array
    {
        $registered = array_column(app(Registry::class)->all('pageBlock'), 'class');

        return collect([...self::CORE, ...$registered])
            ->mapWithKeys(fn (string $block): array => [$block::name() => $block])
            ->all();
    }

    /** The content field of one language. */
    public static function builder(string $name): Builder
    {
        return Builder::make($name)
            ->blocks(collect(static::all())->map(fn (string $block) => $block::make())->values()->all())
            ->collapsible()
            ->cloneable()
            ->blockNumbers(false)
            ->addActionLabel(__('page::page.actions.add_block'));
    }

    /** Rich text without uploads: pictures and files always come from the Media Library (FR-GEN-60). */
    public static function richText(string $name): RichEditor
    {
        return RichEditor::make($name)->fileAttachments(false);
    }

    /**
     * Media Library ids used inside the blocks of every language.
     *
     * @param  array<string, list<array{type: string, data: array}>>  $content  locale => blocks
     * @return list<int>
     */
    public static function mediaIds(array $content): array
    {
        $blocks = static::all();
        $ids = [];

        foreach ($content as $locale => $list) {
            foreach ((array) $list as $block) {
                $class = $blocks[$block['type'] ?? ''] ?? null;
                $ids = [...$ids, ...($class ? $class::mediaIds((array) ($block['data'] ?? [])) : [])];
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }
}
