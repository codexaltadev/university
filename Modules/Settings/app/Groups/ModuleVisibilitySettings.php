<?php

namespace Modules\Settings\Groups;

use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Support\Icons\Heroicon;
use Platform\Models\CoreModule;
use Platform\Settings\ModuleSettings;

/** FR-SET-05: active modules whose pages are hidden on the website; admin keeps working. */
class ModuleVisibilitySettings extends ModuleSettings
{
    public function group(): string
    {
        return 'modules';
    }

    public function label(): string
    {
        return __('settings::settings.groups.modules');
    }

    public function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedPuzzlePiece;
    }

    public function sort(): int
    {
        return 50;
    }

    public function defaults(): array
    {
        return ['hidden_on_website' => []];
    }

    public function schema(): array
    {
        return [
            CheckboxList::make('hidden_on_website')->label(__('settings::settings.fields.hidden_on_website'))
                ->helperText(__('settings::settings.help.hidden_on_website'))
                ->options(fn (): array => CoreModule::query()
                    ->where('type', '!=', 'core')->where('status', CoreModule::STATUS_ACTIVE)
                    ->orderBy('name')->pluck('name', 'slug')->all())
                ->columns(3)
                ->columnSpanFull(),
        ];
    }
}
