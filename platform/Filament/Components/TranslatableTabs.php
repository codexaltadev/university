<?php

namespace Platform\Filament\Components;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Platform\Localization\Languages;

/**
 * One tab per active language, default language first (FR-LANG-02, UI/UX Part A §9,
 * Module Guide §8.3). The closure gets the language code and whether it is the
 * default, and returns that language's fields, named "{attribute}.{locale}":
 *
 *     TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault) => [
 *         TextInput::make("question.{$locale}")->required($isDefault),
 *     ])
 *
 * Works with spatie HasTranslations models and translatable settings alike.
 */
class TranslatableTabs extends Tabs
{
    public static function make(string|Htmlable|Closure|null $label = null): static
    {
        return parent::make($label)->contained(false)->columnSpanFull();
    }

    /** @param array<Component>|Schema|Closure(string, bool): array $components */
    public function schema(array|Schema|Closure $components): static
    {
        if (! $components instanceof Closure) {
            return parent::schema($components);
        }

        $languages = app(Languages::class);
        $default = $languages->default();
        $tabs = [];
        $base = null;

        foreach ($languages->labels() as $code => $label) {
            $fields = $components($code, $code === $default);

            // Stable, unique keys from the attribute names: several tab sets can share a form
            $base ??= 'translations-'.collect($fields)
                ->map(fn ($field): ?string => $field instanceof Field ? Str::beforeLast($field->getName(), '.') : null)
                ->filter()->implode('-');

            $tabs[] = Tab::make($label)->key("{$base}-{$code}")->schema($fields);
        }

        return $this->key($base)->tabs($tabs);
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
