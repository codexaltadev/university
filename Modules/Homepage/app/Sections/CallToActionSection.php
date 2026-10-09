<?php

namespace Modules\Homepage\Sections;

use Filament\Forms\Components\TextInput;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;

/** A band with one button, e.g. "Admission open — Apply now" (FR-HOME-02). */
class CallToActionSection extends Section
{
    public static function key(): string
    {
        return 'core.cta';
    }

    public static function label(): string
    {
        return __('homepage::homepage.sections.cta');
    }

    public static function view(): string
    {
        return 'homepage::sections.cta';
    }

    public static function settingsSchema(): array
    {
        return [
            ...TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                TextInput::make("button_label.{$locale}")->label(__('homepage::homepage.fields.button_label')." ({$language})")->maxLength(60),
            ]),
            TextInput::make('button_url')->label(__('homepage::homepage.fields.button_url'))->regex('#^(https?://|/)#')->maxLength(1000)->required(),
        ];
    }

    public function items(SectionContext $ctx): array
    {
        $labels = (array) $ctx->setting('button_label', []);

        return ['label' => $labels[$ctx->locale] ?? collect($labels)->filter()->first(), 'url' => $ctx->setting('button_url')];
    }
}
