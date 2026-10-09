<?php

namespace Modules\Seo\Settings;

use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Seo\Seo;
use Platform\Settings\ModuleSettings;

/**
 * Settings › SEO (FR-SEO-01/03): title pattern, default description and share
 * picture, search engine verification, organisation type for structured data, robots.txt.
 */
class SeoSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'seo';
    }

    public function label(): string
    {
        return __('seo::seo.settings.tab');
    }

    public function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedMagnifyingGlass;
    }

    public function sort(): int
    {
        return 60;
    }

    public function translatable(): array
    {
        return ['default_description'];
    }

    public function media(): array
    {
        return ['og_image'];
    }

    public function defaults(): array
    {
        return ['title_pattern' => Seo::DEFAULT_PATTERN, 'organization_type' => 'CollegeOrUniversity'];
    }

    public function canAccess(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('Manage:Seo');
    }

    public function schema(): array
    {
        return [
            TextInput::make('title_pattern')->label(__('seo::seo.settings.title_pattern'))
                ->helperText(__('seo::seo.settings.title_pattern_help'))
                ->required()->maxLength(100)->regex('/\{title\}/'),
            Select::make('organization_type')->label(__('seo::seo.settings.organization_type'))
                ->options(['CollegeOrUniversity' => 'College or university', 'EducationalOrganization' => 'Educational organization', 'Organization' => 'Organization'])
                ->selectablePlaceholder(false),
            TranslatableTabs::make()->columnSpanFull()->schema(fn (string $locale): array => [
                Textarea::make("default_description.{$locale}")->label(__('seo::seo.settings.default_description'))->rows(2)->maxLength(160),
            ]),
            MediaPicker::make('og_image')->label(__('seo::seo.settings.og_image'))->image()->storeIds()
                ->helperText(__('seo::seo.settings.og_image_help')),
            TextInput::make('google_verification')->label(__('seo::seo.settings.google_verification'))->maxLength(100)->regex('/^[A-Za-z0-9_\-]+$/'),
            TextInput::make('bing_verification')->label(__('seo::seo.settings.bing_verification'))->maxLength(100)->regex('/^[A-Za-z0-9_\-]+$/'),
            Textarea::make('robots_txt')->label('robots.txt')->rows(6)->columnSpanFull()
                ->placeholder("User-agent: *\nDisallow: /admin")
                ->helperText(__('seo::seo.settings.robots_help')),
        ];
    }
}
