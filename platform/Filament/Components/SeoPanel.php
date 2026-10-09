<?php

namespace Platform\Filament\Components;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Platform\Seo\Seo;

/**
 * Content form "SEO" panel (FR-GEN-70/71, UI/UX Part A §5.2) for HasSeo models:
 * meta title and description per language, share picture, canonical URL, noindex.
 * Every field is optional — empty ones come from the title, excerpt and main picture.
 * Nothing is stored until something is filled in.
 *
 *     SeoPanel::make()
 */
class SeoPanel
{
    public const FIELDS = ['meta_title', 'meta_description', 'og_image', 'canonical_url', 'noindex'];

    public static function make(): Section
    {
        return Section::make(__('platform::seo.panel'))
            ->description(__('platform::seo.panel_help'))
            ->compact()->collapsible()->collapsed()
            ->relationship('seoMeta', condition: fn (?array $state): bool => static::filled($state))
            ->schema([
                ...TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                    TextInput::make("meta_title.{$locale}")->label(__('platform::seo.meta_title')." ({$language})")->maxLength(70)
                        ->helperText($isDefault ? __('platform::seo.meta_title_help', ['pattern' => settings('seo.title_pattern') ?: Seo::DEFAULT_PATTERN]) : null),
                    TextInput::make("meta_description.{$locale}")->label(__('platform::seo.meta_description')." ({$language})")->maxLength(160),
                ]),
                MediaPicker::make('og_image')->label(__('platform::seo.og_image'))->image()
                    ->helperText(__('platform::seo.og_image_help')),
                TextInput::make('canonical_url')->label(__('platform::seo.canonical_url'))->url()->maxLength(2048),
                Toggle::make('noindex')->label(__('platform::seo.noindex'))->helperText(__('platform::seo.noindex_help')),
            ]);
    }

    /** @param array<string, mixed>|null $state */
    protected static function filled(?array $state): bool
    {
        foreach (['meta_title', 'meta_description'] as $key) {
            if (collect((array) ($state[$key] ?? []))->filter(fn ($v): bool => filled($v))->isNotEmpty()) {
                return true;
            }
        }

        return filled($state['canonical_url'] ?? null) || ! empty($state['noindex']) || filled($state['og_image'] ?? null);
    }
}
