<?php

namespace Modules\Accreditation\Settings;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Accreditation & Ranking (FR-ACR-02): an expired accreditation is hidden or
 * shown as "Expired"; how many days before its end the admins are told.
 * Read: settings('accreditation.expired'), settings('accreditation.notify_days').
 */
class AccreditationSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'accreditation';
    }

    public function label(): string
    {
        return __('accreditation::accreditation.title');
    }

    public function defaults(): array
    {
        return ['expired' => 'hide', 'notify_days' => 60];
    }

    public function schema(): array
    {
        return [
            Radio::make('expired')->label(__('accreditation::accreditation.settings.expired'))->inline()
                ->options(['hide' => __('accreditation::accreditation.settings.hide'), 'label' => __('accreditation::accreditation.settings.label')]),
            TextInput::make('notify_days')->label(__('accreditation::accreditation.settings.notify_days'))->integer()->minValue(1)->maxValue(365)->dehydrateStateUsing(fn ($state): int => (int) $state),
        ];
    }
}
