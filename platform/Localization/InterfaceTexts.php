<?php

namespace Platform\Localization;

use Illuminate\Support\Arr;

/**
 * Frontend interface strings the admin may override (FR-LANG-03): every "frontend"
 * group in lang/ (namespace "*") and in active modules' lang folders.
 * Each string: ['id' => 'faq::read_more', 'namespace', 'key', 'default' => lang/ text per language, 'override' => saved texts].
 */
class InterfaceTexts
{
    public const GROUP = 'frontend';

    /** @return array<string, array<string, mixed>> id => string */
    public function all(): array
    {
        $loader = app('translation.loader');
        $files = $loader instanceof DatabaseTranslationLoader ? $loader->fileLoader() : $loader;
        $codes = app(Languages::class)->codes();
        $overrides = Translation::query()->where('group', self::GROUP)->get()->keyBy(fn (Translation $t): string => "{$t->namespace}::{$t->key}");

        $result = [];

        foreach (['*', ...array_keys($files->namespaces())] as $namespace) {
            $byLocale = [];
            foreach ($codes as $code) {
                $byLocale[$code] = Arr::dot($files->load($code, self::GROUP, $namespace === '*' ? null : $namespace));
            }

            $keys = array_unique(array_merge(...array_map('array_keys', array_values($byLocale))));

            foreach ($keys as $key) {
                $id = "{$namespace}::{$key}";
                $result[$id] = [
                    'id' => $id,
                    'namespace' => $namespace,
                    'key' => $key,
                    'default' => array_map(fn (array $lines): ?string => is_string($lines[$key] ?? null) ? $lines[$key] : null, $byLocale),
                    'override' => $overrides->get($id)?->getTranslations('text') ?? [],
                ];
            }
        }

        ksort($result);

        return $result;
    }

    /**
     * Save the admin's texts; empty ones fall back to the lang/ file. No text left: the override is removed.
     *
     * @param  array<string, ?string>  $texts  locale => text
     */
    public function save(string $namespace, string $key, array $texts): void
    {
        $texts = array_filter($texts, fn (?string $text): bool => filled($text));
        $existing = Translation::query()->where(['namespace' => $namespace, 'group' => self::GROUP, 'key' => $key])->first();

        if ($texts === []) {
            $existing?->delete();

            return;
        }

        ($existing ?? new Translation(['namespace' => $namespace, 'group' => self::GROUP, 'key' => $key]))
            ->setTranslations('text', $texts)
            ->save();
    }
}
