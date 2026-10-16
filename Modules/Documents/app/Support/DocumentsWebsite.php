<?php

namespace Modules\Documents\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Modules\Documents\Models\Document;
use Platform\Context\MicrositeResolver;
use Platform\Context\SiteContext;
use Platform\Relations\RelationEngine;
use Platform\Seo\Seo;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Platform\Theme\LayoutResolver;

/**
 * /downloads and /{entity}/downloads (FR-DOC-04): the place's current documents (not replaced by a
 * newer version), newest first; ?type=, ?category= (sub-categories included), ?year=, ?q=; table /
 * cards / list. /downloads/{slug}: details, older versions, PDF preview or flipbook (FR-DOC-03/05).
 * /downloads/{slug}/file: counts the download and sends the file (FR-DOC-06). Private documents
 * only for signed-in users, the file through a signed link (FR-DOC-07).
 */
class DocumentsWebsite
{
    public const VIEWS = ['table', 'cards', 'list'];

    public function respond(string $rest, SiteContext $context, string $locale): Response|RedirectResponse|null
    {
        $rest = trim($rest, '/');
        if ($rest !== Document::PATH && ! Str::startsWith($rest, Document::PATH.'/')) {
            return null;
        }
        $base = $context->isMicrosite() ? rtrim((string) app(MicrositeResolver::class)->urlFor($context->entity, $locale), '/') : '';

        if ($rest === Document::PATH) {
            return $this->listing($context, $base, $locale);
        }

        $tail = Str::after($rest, Document::PATH.'/');
        $file = str_ends_with($tail, '/file');
        $slug = $file ? substr($tail, 0, -5) : $tail;
        if (str_contains($slug, '/') || ! ($found = Document::findBySlug($slug, $locale))) {
            return null;
        }
        [$document, $current] = $found;
        if (! $this->shown($context)->whereKey($document->getKey())->exists()) {
            return null;   // not shown here, inactive, or private for a visitor
        }
        if (! $current) {
            return redirect(Seo::localized($base.'/'.Document::PATH.'/'.$document->slug($locale).($file ? '/file' : ''), $locale), 301);
        }

        if ($file) {
            return $this->download($document);
        }

        app(Seo::class)->forRecord($document);
        if ($document->is_private) {
            app(Seo::class)->set(['noindex' => true]);
        }

        return response()->view('documents::show', [
            'document' => $document,
            'reader' => self::readerUrl($document),
            'download' => $document->allow_download ? url($base.'/'.Document::PATH.'/'.$document->slug($locale).'/file') : null,
            'older' => $document->olderVersions()->frontendVisible()->get(),
            'base' => $base,
        ]);
    }

    /** The PDF's address for the preview / flipbook — a signed link for a private document. */
    public static function readerUrl(Document $document): ?string
    {
        $item = $document->fileItem();
        if ($item === null || ! $document->isPdf()) {
            return null;
        }

        return $document->is_private || $item->is_private
            ? URL::temporarySignedRoute('media.file', now()->addHour(), ['item' => $item->id, 'name' => $item->fileName()])
            : $item->url();
    }

    protected function download(Document $document): Response|RedirectResponse|null
    {
        if (! $document->allow_download) {
            return null;
        }
        $item = $document->fileItem();
        $target = match (true) {
            $item !== null && ($document->is_private || $item->is_private) => URL::temporarySignedRoute('media.file', now()->addMinutes(10), ['item' => $item->id, 'name' => $item->fileName()]),
            $item !== null => $item->url(),
            filled($document->external_url) => (string) $document->external_url,
            default => null,
        };
        if ($target === null) {
            return null;
        }
        $document->countDownload();

        return redirect()->away($target);
    }

    /** Visible documents of this place (versions included): the main site's, or an entity's and those below it. */
    protected function shown(SiteContext $context): Builder
    {
        return app(RelationEngine::class)->for($context->isMicrosite() ? $context->key() : RelationEngine::SITE_KEY)
            ->content(Document::class)->includeDescendants($context->isMicrosite())->query()
            ->forVisitor(auth()->check())->newest();
    }

    protected function listing(SiteContext $context, string $base, string $locale): ?Response
    {
        $request = request();
        if ($context->isMicrosite() && ! $this->shown($context)->exists()) {
            return null;
        }
        app(Seo::class)->set(['title' => __('documents::documents.plural'), 'path' => $base.'/'.Document::PATH]);

        $categories = $this->terms();
        $wanted = $request->query('category');
        $category = is_string($wanted) && $wanted !== '' ? $categories->first(fn (Term $t): bool => $t->slug($locale) === $wanted) : null;
        $type = in_array($request->query('type'), Document::TYPES, true) ? (string) $request->query('type') : null;
        $year = preg_match('/^\d{4}$/', (string) $request->query('year')) ? (int) $request->query('year') : null;
        $q = trim((string) $request->query('q'));
        $date = (new Document)->qualifyColumn('active_at');
        $title = (new Document)->qualifyColumn('title');

        $documents = $this->shown($context)->current()
            ->when($category, fn (Builder $query) => $query->whereHas('terms', fn (Builder $t) => $t->whereIn('core_terms.id', [$category->id, ...$category->descendantIds()])))
            ->when($type, fn (Builder $query) => $query->where('document_type', $type))
            ->when($year, fn (Builder $query) => $query->whereYear($date, $year))
            ->when($q !== '', fn (Builder $query) => $query->whereRaw("LOWER(CAST({$title} AS CHAR)) LIKE ?", ['%'.mb_strtolower($q).'%']))
            ->paginate(20)->withQueryString();

        $view = $request->query('view');
        $layout = is_string($view) && in_array($view, self::VIEWS, true) ? $view : (app(LayoutResolver::class)->for('documents.listing') ?? 'table');

        return response()->view('documents::index', [
            'documents' => $documents,
            'types' => array_values(array_intersect(Document::TYPES, $this->shown($context)->current()->reorder()->distinct()->pluck('document_type')->all())),
            'type' => $type,
            'categories' => $categories->whereNull('parent_id')->values(),
            'category' => $category,
            'year' => $year,
            'years' => $this->shown($context)->current()->reorder()->selectRaw("DISTINCT YEAR({$date}) as y")->orderByDesc('y')->pluck('y')->filter()->map(fn ($y) => (int) $y)->values()->all(),
            'q' => $q,
            'layout' => in_array($layout, self::VIEWS, true) ? $layout : 'table',
            'base' => $base,
        ]);
    }

    /** @return Collection<int, Term> in the vocabulary's order */
    protected function terms(): Collection
    {
        $order = array_keys(app(Vocabularies::class)->options(Document::CATEGORY));

        return Term::query()->whereKey($order)->get()->sortBy(fn (Term $t): int => array_search($t->id, $order, true))->values();
    }
}
