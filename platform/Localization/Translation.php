<?php

namespace Platform\Localization;

use Illuminate\Database\Eloquent\Model;
use Platform\Concerns\LogsPlatformActivity;
use Spatie\Translatable\HasTranslations;

/**
 * Admin override of one interface string (core_translations, DB-01 §11.2, FR-LANG-03).
 * The lang/ file stays the default; an override wins for the languages it has.
 */
class Translation extends Model
{
    use HasTranslations;
    use LogsPlatformActivity;

    public static string $titleAttribute = 'key';

    protected $table = 'core_translations';

    protected $fillable = ['namespace', 'group', 'key', 'text', 'updated_by'];

    public array $translatable = ['text'];

    protected static function booted(): void
    {
        static::saving(fn (Translation $t) => $t->updated_by = auth()->id() ?? $t->updated_by);
        static::saved(fn () => DatabaseTranslationLoader::flush());
        static::deleted(fn () => DatabaseTranslationLoader::flush());
    }
}
