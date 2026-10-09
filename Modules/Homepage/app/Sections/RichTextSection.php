<?php

namespace Modules\Homepage\Sections;

use Filament\Forms\Components\RichEditor;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;

/** Free text (FR-HOME-02), per language; no uploads (FR-GEN-60). */
class RichTextSection extends Section
{
    public static function key(): string
    {
        return 'core.rich-text';
    }

    public static function label(): string
    {
        return __('homepage::homepage.sections.rich_text');
    }

    public static function view(): string
    {
        return 'homepage::sections.rich-text';
    }

    public static function settingsSchema(): array
    {
        return TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
            RichEditor::make("body.{$locale}")->label(__('homepage::homepage.fields.text')." ({$language})")->fileAttachments(false),
        ]);
    }

    public function items(SectionContext $ctx): ?string
    {
        $body = (array) $ctx->setting('body', []);

        return $body[$ctx->locale] ?? collect($body)->filter()->first();
    }
}
