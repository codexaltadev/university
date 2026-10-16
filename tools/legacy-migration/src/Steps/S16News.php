<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Legacy\Migration\Support\Normalizer;
use Modules\News\Models\Post;
use Platform\Relations\RelationEngine;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * S16N News (DB-05 v1.2 §3, §18, BRD Q-21): news starts afresh in the new system; DIU's one
 * `blog_posts` post and its `blog_categories` (→ news.category) come over, on the main site,
 * dated when they were written. `lab_news` waits for the Lab module; pictures for the files (S03).
 */
class S16News extends Step
{
    public static function key(): string
    {
        return 'S16N';
    }

    public static function title(): string
    {
        return 'News: blog categories and posts';
    }

    public static function requires(): array
    {
        return ['news'];
    }

    protected function handle(): void
    {
        if (! Schema::connection('legacy')->hasTable('blog_posts')) {
            $this->report->review('No blog_posts table in the legacy database: nothing to migrate.');

            return;
        }

        $vocabulary = app(Vocabularies::class)->find(Post::CATEGORY)->id;
        $terms = [];
        foreach ($this->rows('blog_categories')->sortBy('serial') as $row) {
            $name = Normalizer::text($row->title);
            Vocabularies::ensureTerms(Post::CATEGORY, [['name' => ['en' => $name], 'slug' => $row->slug]]);
            $terms[$row->id] = Term::query()->where('vocabulary_id', $vocabulary)->get()
                ->first(fn (Term $t): bool => $t->getTranslation('name', 'en', false) === $name)?->id;
        }

        $engine = app(RelationEngine::class);
        $pictures = 0;
        foreach ($this->rows('blog_posts', fn ($q) => $q->whereNull('deleted_at')) as $row) {
            $title = Normalizer::text($row->title);
            if (blank($title)) {
                $this->report->skipped('blog_posts', 'no title');

                continue;
            }
            $pictures += filled($row->image) || filled($row->thumb) ? 1 : 0;
            $categoryIds = collect(json_decode((string) $row->blog_category_ids, true) ?: explode(',', (string) $row->blog_category_ids))
                ->map(fn ($id) => $terms[(int) $id] ?? null)->filter()->values()->all();

            $this->upsert('blog_posts', $row, Post::class, [
                'title' => Normalizer::en($title),
                'excerpt' => Normalizer::en(Normalizer::text(strip_tags((string) $row->excerpt)) ?: null),
                'body' => Normalizer::en(Normalizer::html($row->detail_content)),
                'active_at' => $row->created_at,
                'is_active' => (bool) $row->publication_status,
            ], after: function (Post $post, bool $created) use ($row, $categoryIds, $engine): void {
                $this->keepSlug($post, Str::slug((string) $row->slug), $created);
                $post->syncTerms(Post::CATEGORY, $categoryIds);
                $engine->sync($post, [], mainSite: true, user: null);   // DIU's blog was the main site's
            });
        }

        if ($pictures > 0) {
            $this->report->review("{$pictures} post pictures wait for the files (S03).");
        }
        if (Schema::connection('legacy')->hasTable('lab_news')) {
            $this->report->review($this->legacy()->table('lab_news')->count().' lab news wait for the Lab module.');
        }
    }
}
