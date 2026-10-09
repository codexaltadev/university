<?php

namespace Modules\Settings\Groups;

use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Platform\Settings\ModuleSettings;

/** FR-SET-09: analytics IDs and custom header / footer code — Super Admin only (BRL-SET-02). */
class CodeSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'code';
    }

    public function label(): string
    {
        return __('settings::settings.groups.code');
    }

    public function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedCodeBracket;
    }

    public function sort(): int
    {
        return 80;
    }

    /** Printed by the theme, never handed out by the API. */
    public function private(): array
    {
        return ['header_code', 'footer_code'];
    }

    public function canAccess(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function schema(): array
    {
        return [
            TextInput::make('google_analytics_id')->label('Google Analytics ID')->placeholder('G-XXXXXXXXXX')
                ->regex('/^G-[A-Z0-9]{4,20}$/')->maxLength(30),
            TextInput::make('google_tag_manager_id')->label('Google Tag Manager ID')->placeholder('GTM-XXXXXXX')
                ->regex('/^GTM-[A-Z0-9]{4,20}$/')->maxLength(30),
            Textarea::make('header_code')->label(__('settings::settings.fields.header_code'))
                ->helperText(__('settings::settings.help.custom_code'))->rows(5)->extraInputAttributes(['class' => 'font-mono'])->columnSpanFull(),
            Textarea::make('footer_code')->label(__('settings::settings.fields.footer_code'))
                ->rows(5)->extraInputAttributes(['class' => 'font-mono'])->columnSpanFull(),
        ];
    }
}
