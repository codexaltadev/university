<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Administration\Models\AdministrationCategory;
use Platform\Localization\Languages;

/**
 * Sections of the directory get their own address, /administration/{slug} (DIU /admin/joint-director),
 * in place of ?category={id}: a slug for every category made before (new ones get it when created).
 */
return new class extends Migration
{
    public function up(): void
    {
        AdministrationCategory::withTrashed()->orderBy('id')->each(function (AdministrationCategory $category): void {
            foreach (app(Languages::class)->codes() as $locale) {
                if ($category->slug($locale) === null) {
                    $category->changeSlug($locale, (string) ($category->getTranslation('name', $locale, false) ?: $category->getTranslation('name', app(Languages::class)->default())));
                }
            }
        });
    }

    public function down(): void
    {
        AdministrationCategory::withTrashed()->each(fn (AdministrationCategory $category) => $category->slugs()->delete());
    }
};
