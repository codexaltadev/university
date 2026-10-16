<?php

namespace Modules\Alumni\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Alumni\Models\Alumnus;
use Platform\Context\MicrositeResolver;
use Platform\Context\SiteContext;
use Platform\Seo\Seo;
use Platform\Theme\LayoutResolver;

/**
 * /alumni (FR-ALUM-02): active alumni, Pride first; filters department, program, batch, year,
 * job type and a search over name / designation / organization (AC-ALUM-01); grid / cards /
 * list. A Department or Program microsite's /alumni shows its own (FR-ALUM-05). /alumni/{slug}:
 * photo, what they do, story, video, public contacts (FR-ALUM-03). /alumni/submit: the form.
 */
class AlumniWebsite
{
    public const VIEWS = ['grid', 'cards', 'list'];

    public const PER_PAGE = 24;

    public function respond(string $rest, SiteContext $context, string $locale): Response|RedirectResponse|null
    {
        $rest = trim($rest, '/');
        if ($rest !== Alumnus::PATH && ! Str::startsWith($rest, Alumnus::PATH.'/')) {
            return null;
        }
        $owner = $this->owner($context);
        if ($context->isMicrosite() && $owner === null) {
            return null;   // only Department and Program microsites have alumni
        }
        $base = $context->isMicrosite() ? rtrim((string) app(MicrositeResolver::class)->urlFor($context->entity, $locale), '/') : '';

        if ($rest === Alumnus::PATH) {
            app(Seo::class)->set(['title' => __('alumni::alumni.plural'), 'path' => $base.'/'.Alumnus::PATH]);

            return response()->view('alumni::index', $this->directory($owner) + ['base' => $base, 'microsite' => $context->isMicrosite()]);
        }

        $slug = Str::after($rest, Alumnus::PATH.'/');
        if ($slug === 'submit' && ! $context->isMicrosite()) {
            app(Seo::class)->set(['title' => __('alumni::alumni.submit.title'), 'path' => '/'.Alumnus::PATH.'/submit']);

            return response()->view('alumni::submit');
        }
        if ($context->isMicrosite() || str_contains($slug, '/') || ! ($found = Alumnus::findBySlug($slug, $locale))) {
            return null;
        }
        [$alumnus, $current] = $found;
        if (! $alumnus->isFrontendVisible()) {
            return null;   // a submission not yet checked (AC-ALUM-02)
        }
        if (! $current) {
            return redirect(Seo::localized($alumnus->url($locale), $locale), 301);
        }

        app(Seo::class)->forRecord($alumnus);

        return response()->view('alumni::show', ['alumnus' => $alumnus, 'person' => $alumnus->personPresenter()]);
    }

    /** ['department_id' => 3] or ['program_id' => 7] for a microsite of either; null otherwise. */
    protected function owner(SiteContext $context): ?array
    {
        if (! $context->isMicrosite()) {
            return [];
        }

        return match ($context->entity->getMorphClass()) {
            'department.department' => ['department_id' => $context->entity->getKey()],
            'program.program' => ['program_id' => $context->entity->getKey()],
            default => null,
        };
    }

    /** @return array<string, mixed> */
    protected function directory(array $owner): array
    {
        $request = request();
        $base = Alumnus::query()->frontendVisible()->where($owner);

        $query = (clone $base)
            ->when($request->filled('department') && ! isset($owner['department_id']) && ! isset($owner['program_id']), fn (Builder $q) => $q->where('department_id', (int) $request->query('department')))
            ->when($request->filled('program') && ! isset($owner['program_id']), fn (Builder $q) => $q->where('program_id', (int) $request->query('program')))
            ->when($request->filled('batch'), fn (Builder $q) => $q->where('batch', (string) $request->query('batch')))
            ->when($request->filled('year'), fn (Builder $q) => $q->where('passing_year', (int) $request->query('year')))
            ->when(in_array($request->query('job'), Alumnus::JOB_TYPES, true), fn (Builder $q) => $q->where('job_type', (string) $request->query('job')))
            ->when(filled($search = trim((string) $request->query('q'))), fn (Builder $q) => $q->where(function (Builder $w) use ($search): void {
                $like = '%'.mb_strtolower(addcslashes($search, '%_\\')).'%';
                foreach (['name', 'current_designation', 'organization'] as $column) {
                    $w->orWhereRaw('LOWER(CAST('.$column.' AS CHAR)) LIKE ?', [$like]);
                }
            }))
            ->orderByDesc('is_pride')->ordered()->orderByDesc('passing_year')->orderBy('id');

        $layout = $request->query('view');
        $layout = is_string($layout) && in_array($layout, self::VIEWS, true) ? $layout : (app(LayoutResolver::class)->for('alumni.directory') ?? 'grid');

        return [
            'alumni' => $query->paginate(self::PER_PAGE)->withQueryString(),
            'departments' => isset($owner['department_id']) || isset($owner['program_id']) ? collect() : $this->options(Alumnus::DEPARTMENT, (clone $base)->distinct()->pluck('department_id')),
            'programs' => isset($owner['program_id']) ? collect() : $this->options(Alumnus::PROGRAM, (clone $base)->distinct()->pluck('program_id')),
            'batches' => (clone $base)->whereNotNull('batch')->distinct()->orderBy('batch')->pluck('batch'),
            'years' => (clone $base)->whereNotNull('passing_year')->distinct()->orderByDesc('passing_year')->pluck('passing_year'),
            'layout' => in_array($layout, self::VIEWS, true) ? $layout : 'grid',
        ];
    }

    /** Departments / programs some visible alumnus belongs to (the module may be off). */
    protected function options(string $class, Collection $ids): Collection
    {
        $ids = $ids->filter()->values()->all();

        return $ids === [] || ! class_exists($class) ? collect() : $class::query()->frontendVisible()->whereKey($ids)->ordered()->get();
    }
}
