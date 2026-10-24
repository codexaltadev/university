<?php

namespace Platform\Homepage;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Illuminate\Support\Str;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Localization\Languages;
use Platform\Media\MediaItem;
use Platform\Theme\Themes;

/**
 * Homepage sections a theme declares in theme.json "sections" (owner decision 2026-10-17,
 * Theme Guide §8.4). A theme has no PHP, so it describes its fields; the builder makes
 * the form from them and the theme's view sections/{name} draws them:
 *
 *     "sections": {
 *       "mission": {
 *         "label": "Mission and rankings",
 *         "source": "accreditation.recognition",          ← optional: a module section's items
 *         "fields": [
 *           { "key": "headline", "type": "text", "label": "Headline" },
 *           { "key": "points", "type": "repeater", "label": "Mission points",
 *             "fields": [{ "key": "text", "type": "text", "label": "Point" }] }
 *         ]
 *       }
 *     }
 *
 * Values are kept in the section's settings under "content"; the view gets them for the
 * page's language as $section->content (images as ['url', 'alt']) and the source's items
 * as $items.
 */
class ThemeSections
{
    public const PREFIX = 'theme.';

    public const FIELD_TYPES = ['text', 'textarea', 'richtext', 'url', 'number', 'toggle', 'select', 'image', 'repeater'];

    /** Text types are written per language unless "translatable": false */
    public const TRANSLATABLE = ['text', 'textarea', 'richtext'];

    /**
     * @param  array<string, SectionType>  $catalogue  class sections, for "source"
     * @return array<string, SectionType>
     */
    public static function types(array $catalogue): array
    {
        $types = [];

        foreach (app(Themes::class)->manifest()->sections() as $name => $definition) {
            $fields = array_values((array) ($definition['fields'] ?? []));
            $source = $catalogue[$definition['source'] ?? ''] ?? null;
            $key = self::PREFIX.$name;

            $types[$key] = new SectionType(
                key: $key,
                label: (string) ($definition['label'] ?? Str::headline($name)),
                view: "theme::sections.{$name}",
                model: $source?->model,
                schema: fn (): array => [...($source?->schema() ?? []), Group::make(self::form($fields))->statePath('content')],
                items: fn (SectionContext $ctx): mixed => $source?->items($ctx),
                // + the theme's default heading / subheading, for when the builder's are left empty;
                // {name} {short} {parent} {base} filled for the microsite shown (a kind's shared layout)
                content: fn (SectionContext $ctx): array => self::fill([
                    'heading' => $definition['heading'] ?? null,
                    'subheading' => $definition['subheading'] ?? null,
                    ...self::values($fields, (array) $ctx->setting('content', []), $ctx->locale),
                ], self::placeholders($ctx)),
                defaultMode: $source?->defaultMode ?? 'highlighted_first',
            );
        }

        return $types;
    }

    /** @param list<array<string, mixed>> $fields */
    public static function form(array $fields): array
    {
        return collect($fields)->flatMap(function (array $field): array {
            $key = (string) $field['key'];
            $label = (string) ($field['label'] ?? Str::headline($key));
            $type = (string) ($field['type'] ?? 'text');

            if (self::translatable($field)) {
                return TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                    self::input($type, "{$key}.{$locale}", "{$label} ({$language})", $field),
                ]);
            }

            return [self::input($type, $key, $label, $field)];
        })->all();
    }

    /** @param array<string, mixed> $field */
    protected static function input(string $type, string $name, string $label, array $field): mixed
    {
        $input = self::make($type, $name, $label, $field);

        // An empty field shows the theme's default on the website: say so in the form
        return is_string($field['default'] ?? null) && method_exists($input, 'placeholder') && $type !== 'image'
            ? $input->placeholder($field['default'])
            : $input;
    }

    /** @param array<string, mixed> $field */
    protected static function make(string $type, string $name, string $label, array $field): mixed
    {
        return match ($type) {
            'textarea' => Textarea::make($name)->label($label)->rows(3),
            'richtext' => RichEditor::make($name)->label($label)->fileAttachments(false),
            'url' => TextInput::make($name)->label($label)->regex('#^(https?://|/|mailto:|tel:)#')->maxLength(1000),
            'number' => TextInput::make($name)->label($label)->numeric(),
            'toggle' => Toggle::make($name)->label($label)->default((bool) ($field['default'] ?? false)),
            'select' => Select::make($name)->label($label)
                ->options(collect((array) ($field['options'] ?? []))->mapWithKeys(fn ($o): array => [(string) $o => Str::headline((string) $o)])->all()),
            'image' => MediaPicker::make($name)->label($label)->image()->storeIds(),
            'repeater' => Repeater::make($name)->label($label)
                ->schema(self::form(array_values((array) ($field['fields'] ?? []))))
                ->reorderable()->collapsible()->cloneable()->defaultItems(0)
                ->maxItems(isset($field['max']) ? (int) $field['max'] : null),
            default => TextInput::make($name)->label($label)->maxLength(500),
        };
    }

    /**
     * The values for the website in one language: texts picked (default language when
     * empty), pictures as ['url', 'alt'], repeater rows the same way.
     *
     * @param  list<array<string, mixed>>  $fields
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public static function values(array $fields, array $values, string $locale): array
    {
        $default = app(Languages::class)->default();
        $out = [];

        foreach ($fields as $field) {
            $key = (string) $field['key'];
            $value = $values[$key] ?? null;
            // Nothing entered yet: the theme's "default" (text, rows, or for a picture a file in its public/)
            if (blank(is_array($value) ? array_filter($value, fn ($v): bool => filled($v)) : $value) && array_key_exists('default', $field)) {
                $value = $field['default'];
            }

            $out[$key] = match (true) {
                self::translatable($field) => is_array($value) ? (filled($value[$locale] ?? null) ? $value[$locale] : ($value[$default] ?? null)) : $value,
                ($field['type'] ?? null) === 'image' => is_string($value) && ! ctype_digit($value) ? ['url' => theme_asset($value), 'alt' => ''] : self::image($value, $locale),
                ($field['type'] ?? null) === 'repeater' => array_values(array_map(
                    fn ($row): array => self::values(array_values((array) ($field['fields'] ?? [])), (array) $row, $locale),
                    (array) $value,
                )),
                ($field['type'] ?? null) === 'toggle' => (bool) $value,
                default => $value,
            };
        }

        return $out;
    }

    /**
     * What {name}, {short}, {parent} and {base} stand for on this homepage: the microsite shown
     * (its name, short name, parent's name, its address); the site's name and "" on the main site.
     *
     * @return array<string, string>
     */
    public static function placeholders(SectionContext $ctx): array
    {
        $site = $ctx->site();
        $identity = $site->identity();
        $siteName = (string) settings('general.site_name');

        return [
            '{name}' => $identity['name'] ?? $siteName,
            '{short}' => (string) ($site->entity?->short_name ?: ($identity['name'] ?? $siteName)),
            '{parent}' => $identity['parent']['name'] ?? $identity['name'] ?? $siteName,
            '{base}' => rtrim((string) ($identity['url'] ?? ''), '/'),
        ];
    }

    /**
     * @param  array<string, string>  $map
     */
    public static function fill(mixed $value, array $map): mixed
    {
        return match (true) {
            is_string($value) => strtr($value, $map),
            is_array($value) => array_map(fn ($v) => self::fill($v, $map), $value),
            default => $value,
        };
    }

    /** @param array<string, mixed> $field */
    protected static function translatable(array $field): bool
    {
        return in_array($field['type'] ?? 'text', self::TRANSLATABLE, true) && ($field['translatable'] ?? true);
    }

    /** @return array{url: string, alt: string}|null */
    protected static function image(mixed $value, string $locale): ?array
    {
        $id = is_array($value) ? reset($value) : $value;
        $media = $id ? MediaItem::query()->where('is_active', true)->find($id) : null;

        return $media && ($url = $media->url())
            ? ['url' => $url, 'alt' => (string) $media->getTranslation('alt_text', $locale, true)]
            : null;
    }

    /**
     * theme.json "sections" checks (FR-THEME-02).
     *
     * @param  array<string, mixed>  $sections
     * @return list<string> errors
     */
    public static function validate(array $sections, string $themePath): array
    {
        $errors = [];

        foreach ($sections as $name => $definition) {
            if (! preg_match('/^[a-z][a-z0-9-]{1,49}$/', (string) $name)) {
                $errors[] = "theme.json: section \"{$name}\" — names are lowercase letters, digits and dashes.";

                continue;
            }
            if (! is_file("{$themePath}/views/sections/{$name}.blade.php")) {
                $errors[] = "Missing section view: views/sections/{$name}.blade.php";
            }
            $errors = [...$errors, ...self::validateFields((array) ($definition['fields'] ?? []), "section \"{$name}\"")];
        }

        return $errors;
    }

    /** @return list<string> */
    protected static function validateFields(array $fields, string $where): array
    {
        $errors = [];

        foreach ($fields as $field) {
            if (blank($field['key'] ?? null) || ! in_array($field['type'] ?? null, self::FIELD_TYPES, true)) {
                $errors[] = "theme.json: {$where} — every field needs a \"key\" and a \"type\" (".implode(', ', self::FIELD_TYPES).').';

                continue;
            }
            if ($field['type'] === 'repeater') {
                $errors = [...$errors, ...self::validateFields((array) ($field['fields'] ?? []), "{$where} › {$field['key']}")];
            }
        }

        return $errors;
    }
}
