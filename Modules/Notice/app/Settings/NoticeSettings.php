<?php

namespace Modules\Notice\Settings;

use Filament\Forms\Components\TextInput;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Notices: how many days a notice carries the "New" badge (FR-NOT-07) and
 * how many show per page. Read: settings('notice.new_days'), settings('notice.per_page').
 */
class NoticeSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'notice';
    }

    public function label(): string
    {
        return __('notice::notice.plural');
    }

    public function defaults(): array
    {
        return ['new_days' => 7, 'per_page' => 20];
    }

    public function schema(): array
    {
        return [
            TextInput::make('new_days')->label(__('notice::notice.settings.new_days'))->helperText(__('notice::notice.settings.new_days_help'))
                ->integer()->minValue(0)->maxValue(60)->required(),
            TextInput::make('per_page')->label(__('notice::notice.settings.per_page'))->integer()->minValue(5)->maxValue(100)->required(),
        ];
    }
}
