<?php

namespace Modules\Homepage\Sections;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;
use Platform\Media\MediaItem;

/** Hero / slider (FR-HOME-02): slides with a picture, title, text and button. */
class HeroSection extends Section
{
    public static function key(): string
    {
        return 'core.hero';
    }

    public static function label(): string
    {
        return __('homepage::homepage.sections.hero');
    }

    public static function view(): string
    {
        return 'homepage::sections.hero';
    }

    public static function settingsSchema(): array
    {
        return [
            Repeater::make('slides')->label(__('homepage::homepage.fields.slides'))
                ->schema([
                    MediaPicker::make('image')->label(__('homepage::homepage.fields.image'))->image()->storeIds()->required(),
                    Grid::make(2)->schema(TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                        TextInput::make("title.{$locale}")->label(__('homepage::homepage.fields.title')." ({$language})")->maxLength(150),
                        TextInput::make("text.{$locale}")->label(__('homepage::homepage.fields.text')." ({$language})")->maxLength(255),
                        TextInput::make("button_label.{$locale}")->label(__('homepage::homepage.fields.button_label')." ({$language})")->maxLength(60),
                    ])),
                    TextInput::make('button_url')->label(__('homepage::homepage.fields.button_url'))->regex('#^(https?://|/)#')->maxLength(1000),
                ])
                ->reorderable()->collapsible()->defaultItems(1),
        ];
    }

    public function items(SectionContext $ctx): array
    {
        $pick = fn ($texts): ?string => is_array($texts) ? ($texts[$ctx->locale] ?? collect($texts)->filter()->first()) : null;

        return collect((array) $ctx->setting('slides', []))->map(function (array $slide) use ($pick): ?array {
            $image = MediaItem::query()->where('is_active', true)->find($slide['image'] ?? null);

            return $image ? [
                'image' => $image->url('large'),
                'alt' => (string) $image->alt_text,
                'title' => $pick($slide['title'] ?? null),
                'text' => $pick($slide['text'] ?? null),
                'button' => filled($slide['button_url'] ?? null) ? ['label' => $pick($slide['button_label'] ?? null), 'url' => $slide['button_url']] : null,
            ] : null;
        })->filter()->values()->all();
    }
}
