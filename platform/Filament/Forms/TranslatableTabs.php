<?php

namespace Platform\Filament\Forms;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Platform\Localization\Languages;

/**
 * One tab per active language, default language first (FR-LANG-02, UI/UX Part A §9).
 * The closure gets the language code and whether it is the default, and returns
 * the fields for that language, named "{attribute}.{locale}":
 *
 *     TranslatableTabs::make(fn (string $locale, bool $default) => [
 *         TextInput::make("question.{$locale}")->required($default),
 *     ])
 *
 * Works with spatie HasTranslations models and translatable settings alike.
 */
class TranslatableTabs
{
    /** @param Closure(string, bool): array $fields */
    public static function make(Closure $fields, string $name = 'translations'): Tabs
    {
        $languages = app(Languages::class);
        $default = $languages->default();

        return Tabs::make($name)
            ->tabs(collect($languages->labels())
                ->map(fn (string $label, string $code): Tab => Tab::make($label)
                    ->key("{$name}-{$code}")
                    ->schema($fields($code, $code === $default)))
                ->values()
                ->all())
            ->contained(false)
            ->columnSpanFull();
    }

    /**
     * The same fields for every active language one after another, without tabs
     * (inside repeaters). Put the language in the label: $label is its native name.
     *
     * @param  Closure(string, bool, string): array  $fields  locale, is default, label
     * @return list<Component|Field>
     */
    public static function inline(Closure $fields): array
    {
        $languages = app(Languages::class);
        $default = $languages->default();

        return collect($languages->labels())
            ->flatMap(fn (string $label, string $code): array => $fields($code, $code === $default, $label))
            ->values()
            ->all();
    }
}
