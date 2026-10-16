<?php

namespace Modules\Administration\Settings;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Administration (FR-ADMN-05): profile pages on or off, and the text above
 * /administration. Read: settings('administration.profile_pages'), settings('administration.intro').
 */
class AdministrationSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'administration';
    }

    public function label(): string
    {
        return __('administration::administration.plural');
    }

    public function defaults(): array
    {
        return ['profile_pages' => true, 'intro' => null];
    }

    public function translatable(): array
    {
        return ['intro'];
    }

    public function schema(): array
    {
        return [
            Toggle::make('profile_pages')->label(__('administration::administration.settings.profile_pages'))
                ->helperText(__('administration::administration.settings.profile_pages_help')),
            TranslatableTabs::make()->schema(fn (string $locale): array => [
                RichEditor::make("intro.{$locale}")->label(__('administration::administration.settings.intro'))->fileAttachments(false),
            ]),
        ];
    }
}
