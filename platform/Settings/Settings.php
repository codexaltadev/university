<?php

namespace Platform\Settings;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Platform\Events\SettingsUpdated;
use Platform\Localization\Languages;
use Platform\Registry\Registry;

/**
 * Website settings (C-05, core_settings). Read: settings('contact.phone') — a
 * translatable value comes back in the current language, falling back to the
 * default language; a missing value falls back to the tab's defaults().
 * Each group is cached forever as "settings:{group}" and cleared on save (FR-SET-12).
 */
class Settings
{
    /** @var array<string, array<string, array{value: mixed, translatable: bool, encrypted: bool}>> */
    protected array $groups = [];

    public function get(string $path, mixed $default = null, ?string $locale = null): mixed
    {
        [$group, $key] = $this->split($path);
        $row = $this->rows($group)[$key] ?? null;

        if ($row === null) {
            $definition = $this->definition($group);
            $value = $definition?->defaults()[$key] ?? $default;

            return in_array($key, $definition?->translatable() ?? [], true) ? $this->translate($value, $locale) : $value;
        }

        $value = $this->decode($row);

        return $row['translatable'] ? $this->translate($value, $locale) : $value;
    }

    /** @return array<string, mixed> key => stored value (decrypted, every language), over the tab's defaults */
    public function group(string $group): array
    {
        $values = $this->definition($group)?->defaults() ?? [];

        foreach ($this->rows($group) as $key => $row) {
            $values[$key] = $this->decode($row);
        }

        return $values;
    }

    /**
     * Save a tab. Only changed values are written (and logged).
     *
     * @param  array<string, mixed>  $values
     * @param  list<string>  $translatable
     * @param  list<string>  $encrypted
     * @param  list<string>  $private
     * @return list<string> keys that changed
     */
    public function save(string $group, array $values, array $translatable = [], array $encrypted = [], array $private = []): array
    {
        $current = $this->rows($group);
        $defaults = $this->definition($group)?->defaults() ?? [];
        $changed = [];

        foreach ($values as $key => $value) {
            $isEncrypted = in_array($key, $encrypted, true);

            if (in_array($key, $translatable, true) && is_array($value)) {
                $value = array_filter($value, fn ($text): bool => filled($text)) ?: null;   // no language filled = no value
            }

            $before = isset($current[$key]) ? $this->decode($current[$key]) : ($defaults[$key] ?? null);

            if ($before == $value && gettype($before) === gettype($value)) {
                continue;   // unchanged, or still the default: nothing written, nothing logged
            }

            Setting::query()->updateOrCreate(['group' => $group, 'key' => $key], [
                'value' => $isEncrypted && $value !== null ? Crypt::encryptString(json_encode($value)) : $value,
                'is_translatable' => in_array($key, $translatable, true),
                'is_encrypted' => $isEncrypted,
                'is_public' => ! in_array($key, $private, true),
                'updated_by' => auth()->id(),
            ]);

            $changed[] = $key;
        }

        if ($changed !== []) {
            $this->flush($group);
            SettingsUpdated::dispatch($group, $changed);
        }

        return $changed;
    }

    /** Save a tab by its definition (field types from the ModuleSettings class). */
    public function saveFor(ModuleSettings $definition, array $values): array
    {
        return $this->save($definition->group(), $values, $definition->translatable(), $definition->encrypted(), $definition->private());
    }

    /** Remove a whole group: module uninstall with data, "Reset to neutral". */
    public function forgetGroup(string $group): void
    {
        Setting::query()->where('group', $group)->get()->each->delete();
        $this->flush($group);
        SettingsUpdated::dispatch($group, []);
    }

    /** {"en": …, "bn": …} → the value in $locale, else the default language, else any. */
    public function translate(mixed $value, ?string $locale = null): mixed
    {
        if (! is_array($value) || array_is_list($value)) {
            return $value;
        }

        $locale ??= app()->getLocale();
        $default = app(Languages::class)->default();

        foreach ([$locale, $default] as $code) {
            if (($value[$code] ?? null) !== null && $value[$code] !== '') {
                return $value[$code];
            }
        }

        return collect($value)->first(fn ($v) => $v !== null && $v !== '');
    }

    /** FR-SET-05: an active module whose pages are hidden on the website (admin unchanged). */
    public function moduleHiddenOnWebsite(string $slug): bool
    {
        return in_array($slug, (array) $this->get('modules.hidden_on_website', []), true);
    }

    /** The registered tab of a group, if its module is active. */
    public function definition(string $group): ?ModuleSettings
    {
        foreach ($this->definitions() as $definition) {
            if ($definition->group() === $group) {
                return $definition;
            }
        }

        return null;
    }

    /** @return list<ModuleSettings> tabs of active modules, in tab order */
    public function definitions(): array
    {
        return collect(app(Registry::class)->all('settings'))
            ->map(fn (array $entry): ModuleSettings => app($entry['class']))
            ->sortBy(fn (ModuleSettings $d): string => sprintf('%05d-%s', $d->sort(), $d->group()))
            ->values()
            ->all();
    }

    public function flush(?string $group = null): void
    {
        if ($group === null) {
            foreach (array_keys($this->groups) as $cached) {
                Cache::forget("settings:{$cached}");
            }
            $this->groups = [];

            return;
        }

        unset($this->groups[$group]);
        Cache::forget("settings:{$group}");
    }

    /** @return array<string, array{value: mixed, translatable: bool, encrypted: bool}> */
    protected function rows(string $group): array
    {
        if (isset($this->groups[$group])) {
            return $this->groups[$group];
        }

        try {
            return $this->groups[$group] = Cache::rememberForever("settings:{$group}", fn (): array => Setting::query()
                ->where('group', $group)->get()
                ->mapWithKeys(fn (Setting $s): array => [$s->key => ['value' => $s->value, 'translatable' => $s->is_translatable, 'encrypted' => $s->is_encrypted]])
                ->all());
        } catch (QueryException) {
            return [];   // table not migrated yet
        }
    }

    /** @param array{value: mixed, translatable: bool, encrypted: bool} $row */
    protected function decode(array $row): mixed
    {
        if (! $row['encrypted'] || $row['value'] === null) {
            return $row['value'];
        }

        return json_decode(Crypt::decryptString($row['value']), true);
    }

    /** @return array{0: string, 1: string} */
    protected function split(string $path): array
    {
        $group = strstr($path, '.', true);

        return $group === false ? [$path, ''] : [$group, substr($path, strlen($group) + 1)];
    }
}
