<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Facades\Schema;
use Legacy\Migration\Support\Normalizer;
use Modules\Page\Models\Page;
use Platform\Relations\RelationEngine;

/**
 * S15A main-site articles (Data Migration Plan §5 S15, DB-02 §18): DIU's `articles` (International
 * Advisor, Visiting Professors, Credit Transfer, Rules and Regulation …) → main-site Pages under a page
 * "article", so every old address /article/{slug} stays as it was. The text goes into one text block,
 * its relative pictures and file links pointed at webbackend (Normalizer::legacyHtml, as DIU's frontend
 * does) until S03 takes them into the Media Library. Run again: updates, no copies.
 */
class S15Articles extends Step
{
    public const PARENT_SLUG = 'article';

    public static function key(): string
    {
        return 'S15A';
    }

    public static function title(): string
    {
        return 'Main-site articles as pages under /article';
    }

    public static function requires(): array
    {
        return ['page'];
    }

    protected function handle(): void
    {
        if (! Schema::connection('legacy')->hasTable('articles')) {
            $this->report->review('No articles table in the legacy database: nothing to migrate.');

            return;
        }
        $parent = $this->parent();

        foreach ($this->rows('articles') as $row) {
            $title = Normalizer::text($row->article_title);
            $slug = trim((string) $row->article_slug);
            if ($title === null || $slug === '') {
                $this->report->skipped('articles', 'no title or address');

                continue;
            }

            $this->upsert('articles', $row, Page::class, [
                'owner_type' => RelationEngine::SITE_TYPE,
                'owner_id' => 0,
                'parent_id' => $parent->getKey(),
                'title' => Normalizer::en($title),
                'content' => ['en' => [['type' => 'html', 'data' => ['html' => Normalizer::legacyHtml($row->article_content) ?? '<p></p>']]]],   // as written: the old Bootstrap markup
                'template' => 'default',
                'is_active' => (bool) $row->publication_status,
            ], after: fn (Page $page, bool $created) => $this->keepSlug($page, $slug, $created),
                existing: $this->same(Page::class, ['owner_type' => RelationEngine::SITE_TYPE, 'parent_id' => $parent->getKey(), 'title->en' => $title]));
        }

        $this->report->review('Article pictures and files still load from webbackend.daffodilvarsity.edu.bd: S03 brings them into the Media Library later.');
    }

    /** The main-site page "article" the articles hang under (/article) — made once, kept on later runs */
    protected function parent(): Page
    {
        $page = Page::query()->withoutGlobalScopes()->ownedBy(RelationEngine::SITE_KEY)->whereNull('parent_id')->get()
            ->first(fn (Page $p): bool => $p->slug('en') === self::PARENT_SLUG);

        if ($page === null) {
            $page = Page::query()->create([
                'owner_type' => RelationEngine::SITE_TYPE, 'owner_id' => 0, 'parent_id' => null,
                'title' => ['en' => 'Articles', 'bn' => 'নিবন্ধ'],
                'content' => ['en' => [['type' => 'text', 'data' => ['body' => '<p></p>']]]],
                'template' => 'default', 'is_active' => true,
            ]);
            $this->keepSlug($page, self::PARENT_SLUG, true);
        }

        return $page;
    }
}
