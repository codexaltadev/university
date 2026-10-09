<?php

namespace Platform\Filament\Components;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Platform\Concerns\HasExpiry;
use Platform\Concerns\HasPublishDate;

/**
 * Content form sidebar, "Publishing" (FR-GEN-10/11/13, UI/UX Part A §5.2): Active,
 * Publish date (models with HasPublishDate; future = scheduled), Expires (HasExpiry).
 *
 *     PublishingPanel::make()
 */
class PublishingPanel
{
    public static function make(): Section
    {
        return Section::make(__('platform::relations.publishing'))
            ->compact()
            ->schema([
                Toggle::make('is_active')->label(__('platform::relations.active'))->default(true),
                DateTimePicker::make('published_at')->label(__('platform::relations.published_at'))
                    ->helperText(__('platform::relations.published_at_help'))
                    ->default(fn () => now())
                    ->seconds(false)
                    ->visible(fn (Component $component): bool => static::uses($component, HasPublishDate::class)),
                DateTimePicker::make('expires_at')->label(__('platform::relations.expires_at'))
                    ->seconds(false)
                    ->after('published_at')
                    ->visible(fn (Component $component): bool => static::uses($component, HasExpiry::class)),
            ]);
    }

    protected static function uses(Component $component, string $trait): bool
    {
        $model = $component->getModel();

        return $model !== null && in_array($trait, class_uses_recursive($model), true);
    }
}
