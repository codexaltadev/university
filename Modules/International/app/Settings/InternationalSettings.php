<?php

namespace Modules\International\Settings;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › International (FR-INTL-03): an expired agreement is hidden or marked
 * "Expired"; how many days before its end the admins are told.
 * Read: settings('international.expired'), settings('international.notify_days').
 */
class InternationalSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'international';
    }

    public function label(): string
    {
        return __('international::international.title');
    }

    public function defaults(): array
    {
        return ['expired' => 'hide', 'notify_days' => 60];
    }

    public function schema(): array
    {
        return [
            Radio::make('expired')->label(__('international::international.settings.expired'))->inline()
                ->options(['hide' => __('international::international.settings.hide'), 'label' => __('international::international.settings.label')]),
            TextInput::make('notify_days')->label(__('international::international.settings.notify_days'))->integer()->minValue(1)->maxValue(365)->dehydrateStateUsing(fn ($state): int => (int) $state),
        ];
    }
}
