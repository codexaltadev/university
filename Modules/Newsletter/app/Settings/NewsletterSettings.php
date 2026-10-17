<?php

namespace Modules\Newsletter\Settings;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Newsletter (FR-NLT-05): how many emails go out a minute, the /newsletter
 * page's line, and the note at the bottom of every campaign (address, why they get it).
 * Read: settings('newsletter.per_minute'), settings('newsletter.intro'), settings('newsletter.footer_note').
 */
class NewsletterSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'newsletter';
    }

    public function label(): string
    {
        return __('newsletter::newsletter.title');
    }

    public function defaults(): array
    {
        return ['per_minute' => 60, 'intro' => null, 'footer_note' => null];
    }

    public function translatable(): array
    {
        return ['intro', 'footer_note'];
    }

    public function schema(): array
    {
        return [
            TextInput::make('per_minute')->label(__('newsletter::newsletter.settings.per_minute'))->helperText(__('newsletter::newsletter.settings.per_minute_help'))->integer()->minValue(1)->maxValue(1000)->dehydrateStateUsing(fn ($state): int => (int) $state),
            TranslatableTabs::make()->schema(fn (string $locale): array => [
                Textarea::make("intro.{$locale}")->label(__('newsletter::newsletter.settings.intro'))->rows(2)->maxLength(500),
                Textarea::make("footer_note.{$locale}")->label(__('newsletter::newsletter.settings.footer_note'))->rows(2)->maxLength(500),
            ]),
        ];
    }
}
