<?php

namespace Modules\Waiver\Settings;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Waiver Calculator tab: how matching waiver rules combine (FR-FEE-07) and the
 * calculator's disclaimer (FR-FEE-08). Read: settings('waiver.waiver_mode'), settings('waiver.disclaimer').
 */
class WaiverSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'waiver';
    }

    public function label(): string
    {
        return __('waiver::waiver.calculator.title');
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
            Radio::make('waiver_mode')->label(__('waiver::waiver.settings.waiver_mode'))->required()
                ->options(['highest' => __('waiver::waiver.settings.mode_highest'), 'additive' => __('waiver::waiver.settings.mode_additive')]),
            TranslatableTabs::make()->schema(fn (string $locale): array => [
                Textarea::make("disclaimer.{$locale}")->label(__('waiver::waiver.settings.disclaimer'))->rows(3)->maxLength(1000),
            ]),
        ];
    }
}
