<?php

namespace Modules\News\Settings;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › News: posts per page and whether the author shows. Layouts are chosen in
 * Appearance › Layouts (news.listing, news.detail). Read: settings('news.per_page'), settings('news.show_author').
 */
class NewsSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'news';
    }

    public function label(): string
    {
        return __('news::news.plural');
    }

    public function defaults(): array
    {
        return ['per_page' => 12, 'show_author' => true];
    }

    public function schema(): array
    {
        return [
            TextInput::make('per_page')->label(__('news::news.settings.per_page'))->integer()->minValue(4)->maxValue(60)->required(),
            Toggle::make('show_author')->label(__('news::news.settings.show_author')),
        ];
    }
}
