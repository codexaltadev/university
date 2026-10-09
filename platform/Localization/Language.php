<?php

namespace Platform\Localization;

use Illuminate\Database\Eloquent\Model;
use Platform\Concerns\LogsPlatformActivity;

/**
 * A website language (core_languages, DB-01 §11.1). System table: no soft delete.
 * Exactly one is the default; it can be neither deactivated nor deleted (BRL-LANG-01).
 * Deactivating keeps every translation, the website just stops offering it (BRL-LANG-02).
 */
class Language extends Model
{
    use LogsPlatformActivity;

    public static string $titleAttribute = 'name';

    protected $table = 'core_languages';

    protected $fillable = ['code', 'name', 'native_name', 'direction', 'is_default', 'is_active', 'use_native_digits', 'sort_order'];

    protected $attributes = ['direction' => 'ltr', 'is_default' => false, 'is_active' => true, 'use_native_digits' => false, 'sort_order' => 0];

    protected function casts(): array
    {
        return ['is_default' => 'boolean', 'is_active' => 'boolean', 'use_native_digits' => 'boolean', 'sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (Language $language): void {
            $language->code = strtolower(trim($language->code));

            if ($language->is_default && ! $language->is_active) {
                throw new LanguageRuleViolation(__('platform::localization.default_must_be_active'));
            }

            if (! $language->is_default && $language->getOriginal('is_default') && ! static::otherDefaultExists($language)) {
                throw new LanguageRuleViolation(__('platform::localization.one_default'));
            }
        });

        static::saved(function (Language $language): void {
            if ($language->is_default) {
                static::query()->whereKeyNot($language->getKey())->where('is_default', true)->get()
                    ->each(fn (Language $other) => $other->forceFill(['is_default' => false])->saveQuietly());
            }

            app(Languages::class)->flush();
        });

        static::deleting(function (Language $language): void {
            if ($language->is_default) {
                throw new LanguageRuleViolation(__('platform::localization.default_not_deletable'));
            }
        });

        static::deleted(fn () => app(Languages::class)->flush());
    }

    /** Moving the default goes through the new default, never by unsetting the old one. */
    protected static function otherDefaultExists(Language $language): bool
    {
        return static::query()->whereKeyNot($language->getKey())->where('is_default', true)->exists();
    }
}
