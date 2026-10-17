<?php

namespace Modules\FacultyMember\Settings;

use App\Models\User;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Modules\FacultyMember\Sync\JsonFileSource;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Faculty Members (DB-03 §8.3): where the teachers come from — a JSON file
 * (blank = the sample shipped with the module) or the faculty site's API — daily sync, profile
 * pages and the text above /faculty-members. The source fields are for Super Admins and holders
 * of "Sync:FacultyMember"; `last_sync` is written by the sync itself.
 */
class FacultyMemberSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'faculty-member';
    }

    public function label(): string
    {
        return __('facultymember::facultymember.plural');
    }

    public function defaults(): array
    {
        return [
            'source' => 'json', 'json_path' => null, 'api_url' => null, 'api_token' => null,
            'auto_sync' => true, 'profile_pages' => true, 'intro' => null, 'last_sync' => null,
        ];
    }

    public function translatable(): array
    {
        return ['intro'];
    }

    public function encrypted(): array
    {
        return ['api_token'];
    }

    public function private(): array
    {
        return ['api_token', 'api_url', 'json_path', 'last_sync'];
    }

    public function canAccess(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('Sync:FacultyMember');
    }

    public function schema(): array
    {
        return [
            Section::make(__('facultymember::facultymember.settings.source_section'))
                ->description(__('facultymember::facultymember.settings.source_help'))
                ->schema([
                    Radio::make('source')->label(__('facultymember::facultymember.settings.source'))->inline()->live()
                        ->options(['json' => __('facultymember::facultymember.settings.sources.json'), 'api' => __('facultymember::facultymember.settings.sources.api')]),
                    TextInput::make('json_path')->label(__('facultymember::facultymember.settings.json_path'))->maxLength(500)
                        ->placeholder(str_replace(base_path().'/', '', JsonFileSource::samplePath()))
                        ->helperText(__('facultymember::facultymember.settings.json_path_help'))
                        ->visible(fn (Get $get): bool => $get('source') !== 'api'),
                    TextInput::make('api_url')->label(__('facultymember::facultymember.settings.api_url'))->url()->maxLength(500)
                        ->placeholder('https://faculty.example.edu/api/v1/teachers')
                        ->visible(fn (Get $get): bool => $get('source') === 'api')->required(fn (Get $get): bool => $get('source') === 'api'),
                    TextInput::make('api_token')->label(__('facultymember::facultymember.settings.api_token'))->password()->revealable()->maxLength(500)
                        ->helperText(__('facultymember::facultymember.settings.api_token_help'))
                        ->visible(fn (Get $get): bool => $get('source') === 'api'),
                    Toggle::make('auto_sync')->label(__('facultymember::facultymember.settings.auto_sync'))
                        ->helperText(__('facultymember::facultymember.settings.auto_sync_help')),
                ]),
            Toggle::make('profile_pages')->label(__('facultymember::facultymember.settings.profile_pages'))
                ->helperText(__('facultymember::facultymember.settings.profile_pages_help')),
            TranslatableTabs::make()->schema(fn (string $locale): array => [
                RichEditor::make("intro.{$locale}")->label(__('facultymember::facultymember.settings.intro'))->fileAttachments(false),
            ]),
        ];
    }
}
