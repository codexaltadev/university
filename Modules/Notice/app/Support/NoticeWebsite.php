<?php

namespace Modules\Notice\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Notice\Models\Notice;
use Platform\Context\MicrositeResolver;
use Platform\Context\SiteContext;
use Platform\Relations\ContentRelation;
use Platform\Relations\RelationEngine;
use Platform\Relations\Targets;
use Platform\Seo\Seo;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Platform\Theme\LayoutResolver;

/**
 * Notice Board /notices and /{entity}/notices (FR-NOT-02..04): the place's notices still on the
 * board, pinned first then newest; ?archive=1 the expired ones (FR-NOT-03). Filters category,
 * entity (main board), from / to dates, ?q= over title and reference number; table / list /
 * cards. /notices/{slug}: only where the notice is shown — text, files with titles, PDF preview.
 */
class NoticeWebsite
{
    public const VIEWS = ['table', 'list', 'cards'];

    public function respond(string $rest, SiteContext $context, string $locale): Response|RedirectResponse|null
    {
        $rest = trim($rest, '/');
        if ($rest !== Notice::PATH && ! Str::startsWith($rest, Notice::PATH.'/')) {
            return null;
        }
        $base = $context->isMicrosite() ? rtrim((string) app(MicrositeResolver::class)->urlFor($context->entity, $locale), '/') : '';

        if ($rest === Notice::PATH) {
            return $this->board($context, $base, $locale);
        }

        $slug = Str::after($rest, Notice::PATH.'/');
        if (str_contains($slug, '/') || ! ($found = Notice::findBySlug($slug, $locale))) {
            return null;
        }
        [$notice, $current] = $found;
        if (! $this->shown($context)->whereKey($notice->getKey())->exists()) {
            return null;   // not shown in this place, inactive or scheduled
        }
        if (! $current) {
            return redirect(Seo::localized($base.'/'.Notice::PATH.'/'.$notice->slug($locale), $locale), 301);
        }

        $notice->countView();
        app(Seo::class)->forRecord($notice);

        return response()->view('notice::show', ['notice' => $notice, 'attachments' => $notice->mediaUsagesIn('attachments'), 'base' => $base, 'locale' => $locale]);
    }

    protected function shown(SiteContext $context): Builder
    {
        return app(RelationEngine::class)->for($context->isMicrosite() ? $context->key() : RelationEngine::SITE_KEY)
            ->content(Notice::class)->includeDescendants($context->isMicrosite())->query()->reorder();
    }

    protected function board(SiteContext $context, string $base, string $locale): ?Response
    {
        $request = request();
        if ($context->isMicrosite() && ! $this->shown($context)->exists()) {
            return null;
        }
        app(Seo::class)->set(['title' => __('notice::notice.plural'), 'path' => $base.'/'.Notice::PATH]);

        $archive = $request->boolean('archive');
        $categories = $this->terms();
        $category = is_string($request->query('category')) ? $categories->first(fn (Term $t): bool => $t->slug($locale) === $request->query('category')) : null;
        $entities = $context->isMicrosite() ? collect() : $this->entities();
        $entity = is_string($request->query('entity')) && $entities->has($request->query('entity')) ? $request->query('entity') : null;
        $date = fn (string $key): ?Carbon => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query($key)) ? Carbon::parse((string) $request->query($key)) : null;
        [$from, $to] = [$date('from'), $date('to')];

        $notices = $this->shown($context)
            ->when(! $archive, fn (Builder $query) => $query->onBoard())->when($archive, fn (Builder $query) => $query->archived())
            ->when($category, fn (Builder $query) => $query->whereHas('terms', fn (Builder $t) => $t->whereIn('core_terms.id', [$category->id, ...$category->descendantIds()])))
            ->when($entity, fn (Builder $query) => $query->shownIn([$entity]))
            ->when($from, fn (Builder $query) => $query->where('active_at', '>=', $from->copy()->startOfDay()))
            ->when($to, fn (Builder $query) => $query->where('active_at', '<=', $to->copy()->endOfDay()))
            ->when(filled($search = trim((string) $request->query('q'))), fn (Builder $query) => $query->where(function (Builder $w) use ($search): void {
                $w->whereRaw('LOWER(CAST(notice_notices.title AS CHAR)) LIKE ?', ['%'.mb_strtolower(addcslashes($search, '%_\\')).'%'])
                    ->orWhere('notice_notices.reference_no', 'like', '%'.addcslashes($search, '%_\\').'%');
            }))
            ->when($archive, fn (Builder $query) => $query->orderByDesc('active_at'), fn (Builder $query) => $query->boardOrder())
            ->with(['terms'])
            ->paginate((int) (settings('notice.per_page') ?: 20))->withQueryString();

        $view = $request->query('view');
        $layout = is_string($view) && in_array($view, self::VIEWS, true) ? $view : (app(LayoutResolver::class)->for('notice.listing') ?? 'table');

        return response()->view('notice::index', [
            'notices' => $notices,
            'categories' => $categories->whereNull('parent_id')->values(),
            'category' => $category,
            'entities' => $entities,
            'entity' => $entity,
            'archive' => $archive,
            'layout' => in_array($layout, self::VIEWS, true) ? $layout : 'table',
            'base' => $base,
        ]);
    }

    /**
     * Entities some main-site notice is also shown in (the board's "entity" filter).
     *
     * @return Collection<string, string> key => name
     */
    protected function entities(): Collection
    {
        $type = (new Notice)->getMorphClass();
        $onMain = ContentRelation::query()->where('content_type', $type)->where('target_type', RelationEngine::SITE_TYPE)->select('content_id');
        $targets = app(Targets::class);

        return ContentRelation::query()->where('content_type', $type)->where('target_type', '!=', RelationEngine::SITE_TYPE)
            ->whereIn('content_id', $onMain)->select('target_type', 'target_id')->distinct()->toBase()->get()
            ->mapWithKeys(fn ($row): array => [$row->target_type.':'.$row->target_id => $targets->label($row->target_type.':'.$row->target_id)])
            ->sort();
    }

    /** @return Collection<int, Term> in the vocabulary's order */
    protected function terms(): Collection
    {
        $order = array_keys(app(Vocabularies::class)->options(Notice::CATEGORY));

        return Term::query()->whereKey($order)->get()->sortBy(fn (Term $t): int => array_search($t->id, $order, true))->values();
    }
}
