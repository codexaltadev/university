<?php

namespace Platform\Filament\Components;

use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Platform\Context\MicrositeResolver;
use Platform\Context\UrlRules;
use Platform\Localization\Languages;
use Platform\Scope\UserScopeFilter;

/**
 * Web address of a record with a frontend page (FR-GEN-30/32, UI/UX Part A §6):
 * shows the slug in the default language with the address in front, made from the
 * title on create; changing it keeps the old one as history so the old URL redirects.
 * Needs HasSlug on the model.
 *
 *     WebAddressField::make(prefix: fn (Get $get, ?Model $record): string => '/news/')
 *     WebAddressField::forMicrosite(Department::class, parentField: 'faculty_id')   // "/fsit/" in front, clashes refused
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

    /**
     * A microsite entity's address (SRS FR-GEN-34/35): the parent's path in front
     * ("/faculties/", "/fsit/", "/fsit/cse/lab/"), reserved words and taken addresses refused.
     *
     * @param  class-string<Model>  $model
     */
    public static function forMicrosite(string $model, ?string $parentField = null): TextInput
    {
        return static::make(prefix: fn (Get $get): string => static::micrositePrefix($model, $parentField ? $get($parentField) : null))
            ->rules([fn (?Model $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($model, $record): void {
                if (filled($value) && ($record === null || Str::slug($value) !== $record->slug(app(Languages::class)->default()))
                    && ($message = UrlRules::entityClash($model, (string) $value, $record))) {
                    $fail($message);
                }
            }]);
    }

    /** @param class-string<Model> $model */
    protected static function micrositePrefix(string $model, mixed $parentId): string
    {
        $resolver = app(MicrositeResolver::class);
        $entry = $resolver->entities()[$model] ?? null;
        if ($entry === null) {
            return '/';
        }

        $parent = $entry['parent'] && filled($parentId)
            ? UserScopeFilter::withoutScope(fn () => $entry['parent']::query()->find($parentId))
            : null;

        if ($parent === null) {
            return "/{$entry['routePrefix']}/";
        }

        return rtrim((string) $resolver->basePathFor($parent, app(Languages::class)->default()), '/')
            .($entry['childSegment'] ? "/{$entry['childSegment']}" : '').'/';
    }
}
