<?php

namespace Modules\Office\Support;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Modules\Office\Models\Office;
use Modules\Office\Models\OfficeService;
use Modules\Office\Models\OfficeSupportContact;
use Platform\Context\MicrositeResolver;
use Platform\Context\SiteContext;
use Platform\Seo\Seo;
use Platform\Statistics\StatItem;
use Platform\Statistics\StatSet;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Platform\Theme\LayoutResolver;

/**
 * Offices on the website (FR-OFF-06/07/09): /offices grouped by type in the layout chosen in
 * Appearance › Layouts (grid, list, directory) — an office without its own page as a contact
 * card; /services, every office's services; /support-contacts, "who to contact" by purpose.
 * The microsite /office/{slug} comes from the kernel (home: office::microsite.home).
 */
class OfficeWebsite
{
    public const SERVICES = 'services';

    public const SUPPORT = 'support-contacts';

    /** /office/{slug}/team */
    public const TEAM = 'team';

    public function respond(string $rest, SiteContext $context, string $locale): ?Response
    {
        $rest = trim($rest, '/');
        if ($context->isMicrosite()) {
            return $rest === self::TEAM && $context->entity instanceof Office ? $this->team($context->entity) : null;
        }

        return match ($rest) {
            Office::LISTING => $this->listing(),
            self::SERVICES => $this->services(),
            self::SUPPORT => $this->support(),
            default => null,
        };
    }

    /** /office/{slug}/team: the whole team, head first (FR-OFF-05); none, no page */
    protected function team(Office $office): ?Response
    {
        if ($office->people(['head', 'team'])->isEmpty()) {
            return null;
        }
        $base = rtrim((string) app(MicrositeResolver::class)->urlFor($office), '/');
        app(Seo::class)->set(['title' => __('office::office.page.team_members').' — '.$office->name, 'path' => $base.'/'.self::TEAM]);

        return response()->view('office::team', ['entity' => $office]);
    }

    protected function listing(): Response
    {
        $layout = app(LayoutResolver::class)->for('office.listing') ?? 'grid';
        $offices = Office::query()->listed()->with('type')->get();
        app(Seo::class)->set(['title' => __('office::office.plural'), 'path' => '/'.Office::LISTING]);

        return response()->view('office::listing', [
            'groups' => static::byType($offices),
            'layout' => in_array($layout, ['grid', 'list', 'directory'], true) ? $layout : 'grid',
        ]);
    }

    protected function services(): Response
    {
        app(Seo::class)->set(['title' => __('office::office.services.plural'), 'path' => '/'.self::SERVICES]);
        $services = OfficeService::query()->where('is_active', true)->ordered()
            ->whereHas('office', fn ($q) => $q->frontendVisible())->with('office')->get();

        return response()->view('office::services', [
            'groups' => $services->groupBy('office_id')->map(fn (Collection $items): array => ['office' => $items->first()->office, 'services' => $items])
                ->sortBy(fn (array $g): int => (int) $g['office']->sort_order)->values(),
        ]);
    }

    protected function support(): Response
    {
        $contacts = OfficeSupportContact::query()->where('is_active', true)->ordered()
            ->whereHas('office', fn ($q) => $q->frontendVisible())->with('office')->get()
            ->sortBy(fn (OfficeSupportContact $c): string => sprintf('%05d-%05d', $c->office->sort_order, $c->sort_order))->values();
        $page = new SupportDirectory($contacts, request()->only(['q', 'purpose', 'office']));
        app(Seo::class)->set(['title' => $page->title(), 'description' => $page->subtitle(), 'path' => '/'.self::SUPPORT]);

        return response()->view('office::support-contacts', ['contacts' => $contacts, 'page' => $page]);
    }

    /**
     * Offices grouped by type in the vocabulary's order; offices without a type last.
     *
     * @param  Collection<int, Office>  $offices
     * @return Collection<int, array{type: ?Term, offices: Collection<int, Office>}>
     */
    public static function byType(Collection $offices): Collection
    {
        $order = array_keys(app(Vocabularies::class)->options(Office::TYPE));
        $terms = Term::query()->whereKey($order)->get()->sortBy(fn (Term $t): int => array_search($t->id, $order, true))->values();

        return $terms->map(fn (Term $t): array => ['type' => $t, 'offices' => $offices->where('type_term_id', $t->id)->values()])
            ->push(['type' => null, 'offices' => $offices->whereNull('type_term_id')->values()])
            ->filter(fn (array $g): bool => $g['offices']->isNotEmpty())->values();
    }

    /** FR-OFF-14: a new office starts with its own counters — Services and Team (automatic), one manual. */
    public static function createStatSet(Office $office): void
    {
        $key = $office->getMorphClass().':'.$office->getKey();
        if (StatSet::query()->ownedBy($key)->where('key', 'default')->exists()) {
            return;
        }

        $set = (new StatSet(['key' => 'default', 'name' => ['en' => 'Office counters', 'bn' => 'Office-এর সংখ্যা']]))->setOwnerKey($key);
        $set->save();

        foreach ([
            ['label' => ['en' => 'Services', 'bn' => 'সেবা'], 'source' => 'metric', 'metric_key' => 'office.services'],
            ['label' => ['en' => 'Team members', 'bn' => 'সদস্য'], 'source' => 'metric', 'metric_key' => 'office.team'],
        ] as $i => $item) {
            StatItem::query()->create([...$item, 'stat_set_id' => $set->id, 'sort_order' => $i + 1]);
        }
    }
}
