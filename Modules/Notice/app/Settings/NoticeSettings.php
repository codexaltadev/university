<?php

namespace Modules\Notice\Settings;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Notices: how many days a notice carries the "New" badge (FR-NOT-07), how many
 * show per page, and the board's own text — the titles on top of the board and of a notice, and
 * the picture there (DIU's are the defaults). Read: settings('notice.new_days'), settings('notice.per_page'),
 * settings('notice.board_title'), settings('notice.notice_title'), media_url(settings('notice.board_image')).
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
        return [
            'new_days' => 7,
            'per_page' => 20,
            'board_title' => ['en' => 'Notice Board', 'bn' => 'নোটিশ বোর্ড'],
            'notice_title' => ['en' => 'Notice Details', 'bn' => 'নোটিশের বিস্তারিত'],
            'board_image' => null,
        ];
    }

    public function translatable(): array
    {
        return ['board_title', 'notice_title'];
    }

    public function media(): array
    {
        return ['board_image'];
    }

    public function schema(): array
    {
        return [
            TextInput::make('new_days')->label(__('notice::notice.settings.new_days'))->helperText(__('notice::notice.settings.new_days_help'))
                ->integer()->minValue(0)->maxValue(60)->required(),
            TextInput::make('per_page')->label(__('notice::notice.settings.per_page'))->integer()->minValue(5)->maxValue(100)->required(),
            Section::make(__('notice::notice.settings.page'))->description(__('notice::notice.settings.page_help'))->compact()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    TextInput::make("board_title.{$locale}")->label(__('notice::notice.settings.board_title'))->maxLength(150),
                    TextInput::make("notice_title.{$locale}")->label(__('notice::notice.settings.notice_title'))->maxLength(150),
                ]),
                MediaPicker::make('board_image')->label(__('notice::notice.settings.board_image'))->image()->storeIds(),
            ]),
        ];
    }
}
