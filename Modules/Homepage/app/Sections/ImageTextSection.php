<?php

namespace Modules\Homepage\Sections;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;
use Platform\Media\MediaItem;

/** Picture beside text, e.g. "About" or the head's message (FR-HOME-02). */
class ImageTextSection extends Section
{
    public static function key(): string
    {
        return 'core.image-text';
    }

    public static function label(): string
    {
        return __('homepage::homepage.sections.image_text');
    }

    public static function view(): string
    {
        return 'homepage::sections.image-text';
    }

    public static function settingsSchema(): array
    {
        return [
            MediaPicker::make('image')->label(__('homepage::homepage.fields.image'))->image()->storeIds(),
            Select::make('image_position')->label(__('homepage::homepage.fields.image_position'))
                ->options(['left' => __('homepage::homepage.positions.left'), 'right' => __('homepage::homepage.positions.right')])
                ->default('left')->selectablePlaceholder(false),
            ...TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                RichEditor::make("body.{$locale}")->label(__('homepage::homepage.fields.text')." ({$language})")->fileAttachments(false),
            ]),
        ];
    }

    public function items(SectionContext $ctx): array
    {
        $image = MediaItem::query()->where('is_active', true)->find($ctx->setting('image'));
        $body = (array) $ctx->setting('body', []);

        return [
            'image' => $image?->url('medium'),
            'alt' => $image ? (string) $image->alt_text : null,
            'position' => $ctx->setting('image_position', 'left'),
            'body' => $body[$ctx->locale] ?? collect($body)->filter()->first(),
        ];
    }
}
