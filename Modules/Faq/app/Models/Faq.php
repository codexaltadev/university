<?php

namespace Modules\Faq\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Faq\Database\Factories\FaqFactory;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasPublishDate;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\IsAttachable;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;

class Faq extends BaseModel implements Scopeable
{
    use AppliesUserScope;   // admin list: only FAQs shown in the user's entities

    /** @use HasFactory<FaqFactory> */
    use HasFactory;

    use HasPublishDate;
    use HasSortOrder;
    use IsAttachable;       // "Show in", main site, highlight (core_relations)

    public static string $titleAttribute = 'question';

    protected $table = 'faq_faqs';

    protected $fillable = ['question', 'answer', 'published_at', 'sort_order', 'is_active'];

    protected static function newFactory(): FaqFactory
    {
        return FaqFactory::new();
    }
}
