<?php

namespace Modules\Faq\Settings;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Platform\Settings\ModuleSettings;

/** Website Settings › FAQ tab (FR-SET-10, Module Guide §12). Read: settings('faq.per_page'). */
class FaqSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'faq';
    }

    public function label(): string
    {
        return __('faq::faq.title');
    }

    public function defaults(): array
    {
        return ['per_page' => 20, 'show_helpful_buttons' => true];
    }

    public function schema(): array
    {
        return [
            TextInput::make('per_page')->label(__('faq::faq.settings.per_page'))
                ->numeric()->integer()->minValue(5)->maxValue(100)->required(),
            Toggle::make('show_helpful_buttons')->label(__('faq::faq.settings.show_helpful_buttons')),
        ];
    }
}
