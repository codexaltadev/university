<?php

namespace Modules\Page\Blocks;

use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Platform\Filament\Components\HtmlEditor;
use Platform\Pages\PageBlock;
use Platform\Theme\Themes;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * HTML as written (tables, cards, accordions of the old site's pages, kept for S15): the rich
 * text editor would drop its classes and Bootstrap attributes, so it is kept as HTML — edited
 * visually or as source in the HtmlEditor, shown with the theme's editor CSS — and cleaned here:
 * safe elements, classes, inline style, collapse attributes; no scripts, handlers or frames.
 * Media Library files inserted in the editor carry data-media-id and count as usage.
 */
final class HtmlBlock extends PageBlock
{
    /** Attributes the old pages' Bootstrap markup needs beyond the safe set */
    public const ATTRIBUTES = [
        'class', 'style', 'id', 'title', 'border', 'cellpadding', 'cellspacing', 'align', 'valign', 'width', 'height',
        'colspan', 'rowspan', 'role', 'aria-expanded', 'aria-controls', 'aria-labelledby',
        'data-media-id', 'data-bs-toggle', 'data-bs-target', 'data-bs-parent', 'data-toggle', 'data-target', 'data-parent', 'target',
    ];

    public static function name(): string
    {
        return 'html';
    }

    public static function label(): string
    {
        return __('page::page.blocks.html');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedCodeBracket;
    }

    public static function schema(): array
    {
        return [HtmlEditor::make('html')->hiddenLabel()->required()->helperText(__('page::page.help.html'))
            ->contentCss(fn (): array => array_map(theme_asset(...), app(Themes::class)->manifest()->editorCss()))];
    }

    /** Media Library files inserted in the editor (data-media-id) */
    public static function mediaIds(array $data): array
    {
        preg_match_all('/data-media-id="(\d+)"/', (string) ($data['html'] ?? ''), $found);

        return array_map('intval', $found[1]);
    }

    public static function clean(?string $html): HtmlString
    {
        if (blank($html)) {
            return new HtmlString('');
        }

        $config = (new HtmlSanitizerConfig)->allowSafeElements()
            ->allowLinkSchemes(['https', 'http', 'mailto', 'tel'])->allowMediaSchemes(['https', 'http'])
            ->allowRelativeLinks()->allowRelativeMedias()
            ->forceAttribute('img', 'loading', 'lazy')->forceAttribute('img', 'decoding', 'async')   // load when scrolled to (Phase 4.8c)
            ->withMaxInputLength(2_000_000);
        foreach (self::ATTRIBUTES as $attribute) {
            $config = $config->allowAttribute($attribute, '*');
        }

        return new HtmlString((new HtmlSanitizer($config))->sanitize($html));
    }
}
