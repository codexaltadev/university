<?php

namespace Modules\Faq\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Faq\Database\Factories\FaqFactory;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\HasTaxonomy;
use Platform\Concerns\IsAttachable;
use Platform\Concerns\IsSearchable;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * A frequently asked question (M-28, DB-05 v1.1 §13): question and answer per language, a
 * category (faq.category), shown in entities and on the main site (core_relations), and
 * "Was this helpful?" counts. Shown when active — no publish date.
 */
class Faq extends BaseModel implements Scopeable
{
    use AppliesUserScope;   // admin list: only FAQs shown in the user's entities

    /** @use HasFactory<FaqFactory> */
    use HasFactory;

    use HasSortOrder;
    use HasTaxonomy;        // faq.category
    use HasTranslations;
    use IsAttachable;       // "Show in", main site, highlight (core_relations)
    use IsSearchable;

    public const CATEGORY = 'faq.category';

    public const PATH = 'faq';

    public static string $titleAttribute = 'question';

    protected $table = 'faq_faqs';

    protected $fillable = ['question', 'answer', 'sort_order', 'is_active'];

    public array $translatable = ['question', 'answer'];

    protected function casts(): array
    {
        return ['helpful_yes' => 'integer', 'helpful_no' => 'integer'];
    }

    /** /faq#faq-12 — the question opens on the FAQ page. */
    public function url(?string $locale = null): string
    {
        return '/'.self::PATH.'#faq-'.$this->getKey();
    }

    protected function searchBodyAttributes(): array
    {
        return ['answer'];
    }

    protected static function newFactory(): FaqFactory
    {
        return FaqFactory::new();
    }
}
