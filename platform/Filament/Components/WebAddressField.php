<?php

namespace Platform\Filament\Components;

use Closure;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Platform\Localization\Languages;

/**
 * Web address of a record with a frontend page (FR-GEN-30/32, UI/UX Part A §6):
 * shows the slug in the default language with the address in front, made from the
 * title on create; changing it keeps the old one as history so the old URL redirects.
 * Needs HasSlug on the model.
 *
 *     WebAddressField::make(prefix: fn (Get $get, ?Model $record): string => '/news/')
 */
class WebAddressField
{
    public static function make(Closure|string|null $prefix = null, string $name = 'web_address'): TextInput
    {
        return TextInput::make($name)
            ->label(__('platform::relations.web_address'))
            ->prefix($prefix)
            ->helperText(__('platform::relations.web_address_help'))
            ->maxLength(191)
            ->rules([fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                if (filled($value) && Str::slug($value) === '') {
                    $fail(__('platform::relations.web_address_help'));
                }
            }])
            ->dehydrated(false)
            ->afterStateHydrated(function (TextInput $component, ?Model $record): void {
                if ($record?->exists && method_exists($record, 'slug')) {
                    $component->state($record->slug(app(Languages::class)->default()));
                }
            })
            ->saveRelationshipsUsing(function (Model $record, ?string $state): void {
                $locale = app(Languages::class)->default();

                if (filled($state) && method_exists($record, 'changeSlug') && Str::slug($state) !== $record->slug($locale)) {
                    $record->changeSlug($locale, $state);
                }
            });
    }
}
