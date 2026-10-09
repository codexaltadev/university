<?php

namespace Modules\Fee\Settings;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Fees tab: how matching waiver rules combine (FR-FEE-07) and the
 * calculator's disclaimer (FR-FEE-08). Read: settings('fee.waiver_mode'), settings('fee.disclaimer').
 */
class FeeSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'fee';
    }

    public function label(): string
    {
        return __('fee::fee.title');
    }

    public function defaults(): array
    {
        return ['waiver_mode' => 'highest', 'disclaimer' => null];
    }

    public function translatable(): array
    {
        return ['disclaimer'];
    }

    public function schema(): array
    {
        return [
            Radio::make('waiver_mode')->label(__('fee::fee.settings.waiver_mode'))->required()
                ->options(['highest' => __('fee::fee.settings.mode_highest'), 'additive' => __('fee::fee.settings.mode_additive')]),
            TranslatableTabs::make()->schema(fn (string $locale): array => [
                Textarea::make("disclaimer.{$locale}")->label(__('fee::fee.settings.disclaimer'))->rows(3)->maxLength(1000),
            ]),
        ];
    }
}
