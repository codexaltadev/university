<?php

namespace Modules\Seo\Settings;

use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Search (Phase 4.7g): the synonyms the website search uses (the kernel reads
 * settings('search.synonyms')) and the "Popular searches" shown in the search window and on an empty
 * search page — the ones entered here, else the most searched terms that found something lately.
 */
class SearchSettings extends ModuleSettings
{
    public const POPULAR = 6;

    public function group(): string
    {
        return 'search';
    }

    public function label(): string
    {
        return __('seo::seo.search.settings');
    }

    public function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedMagnifyingGlass;
    }

    public function defaults(): array
    {
        return [
            'synonyms' => "cse = computer science and engineering\nswe = software engineering\nbba = business administration\neee = electrical and electronic engineering",
            'popular' => null,
            'show_popular' => true,
        ];
    }

    public function canAccess(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('Manage:Seo');
    }

    public function schema(): array
    {
        return [
            Textarea::make('synonyms')->label(__('seo::seo.search.synonyms'))->rows(8)->maxLength(5000)
                ->helperText(__('seo::seo.search.synonyms_help')),
            Toggle::make('show_popular')->label(__('seo::seo.search.show_popular')),
            TextInput::make('popular')->label(__('seo::seo.search.popular'))->maxLength(500)
                ->helperText(__('seo::seo.search.popular_help')),
        ];
    }
}
