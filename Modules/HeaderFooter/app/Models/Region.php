<?php

namespace Modules\HeaderFooter\Models;

use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasOwner;
use Platform\Contracts\Scopeable;
use Platform\Models\ConfigModel;
use Spatie\Translatable\HasTranslations;

/**
 * The top bar, header or footer of the main site or of one microsite
 * (header_footer_regions, P-05). Owners without their own use their parent's,
 * then the main site's (FR-HF-05, BRL-MENU-01). Configuration table: no soft delete.
 */
class Region extends ConfigModel implements Scopeable
{
    use AppliesUserScope;
    use HasOwner;
    use HasTranslations;

    public const REGIONS = ['topbar', 'header', 'footer'];

    public static string $titleAttribute = 'region';

    protected $table = 'header_footer_regions';

    protected $fillable = ['region', 'variant', 'config', 'copyright', 'is_active'];

    public array $translatable = ['copyright'];

    protected function casts(): array
    {
        return ['config' => 'array', 'is_active' => 'boolean'];
    }

    /** FR-HF-04: "© {year} Daffodil International University" with this year. */
    public function copyrightText(?string $locale = null): ?string
    {
        $text = $this->getTranslation('copyright', $locale ?? app()->getLocale());

        return filled($text) ? str_replace('{year}', (string) now()->year, $text) : null;
    }
}
