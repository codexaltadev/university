<?php

namespace Legacy\Migration\Steps;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Legacy\Migration\Support\Normalizer;
use Modules\Committee\Models\Committee;
use Platform\Assignments\Assignment;
use Platform\Relations\RelationEngine;

/**
 * S13 Committee (DB-04 v1.4 §7, §11.1): DIU's `committee_categories` are the committees (Board of
 * Trustees, Syndicate …, on the main site, in DIU's order, as one list with no category — as on
 * the legacy site) and `committees` their members — an
 * Administration member when the name matches one person (titles ignored), else one manual
 * person per name. `position` → committee role (own title kept when it is not the role's
 * name), `header` → note ("Nominated by UGC"). `lab_committees` wait for the Lab module.
 * Run after S11.
 */
class S13Committees extends Step
{
    public static function key(): string
    {
        return 'S13';
    }

    public static function title(): string
    {
        return 'Committee: committees and members';
    }

    public static function requires(): array
    {
        return ['committee'];
    }

    protected function handle(): void
    {
        if (! Schema::connection('legacy')->hasTable('committee_categories')) {
            $this->report->review('No committee tables in the legacy database: nothing to migrate.');

            return;
        }
        $engine = app(RelationEngine::class);
        $committees = [];

        foreach ($this->rows('committee_categories')->sortBy('serial') as $row) {
            $slug = Str::slug((string) $row->slug);
            $committees[$row->id] = $this->upsert('committee_categories', $row, Committee::class, [
                'name' => Normalizer::en(Normalizer::text($row->title)),
                'category_term_id' => null,   // DIU had no categories above its committees
                'sort_order' => max(0, (int) $row->serial),
                'is_active' => (bool) $row->publication_status,
            ], after: function (Committee $committee, bool $created) use ($slug, $engine): void {
                $this->keepSlug($committee, $slug, $created);
                $engine->sync($committee, [], mainSite: true, user: null);   // DIU's committees were the main site's
                if ($slug !== '') {
                    $this->oldAddress('/committee/'.$slug, $committee);   // DIU's /committee/board-of-trustees
                }
            });
        }
        $this->oldAddress('/committee', '/'.Committee::PATH);

        $matched = $manual = 0;
        foreach ($this->rows('committees')->sortBy('serial') as $row) {
            $committee = $committees[$row->committee_category_id] ?? null;
            $name = Normalizer::text($row->title);
            if ($committee === null || blank($name) || ! preg_match('/\pL/u', (string) $name)) {
                $this->report->skipped('committees', $committee === null ? 'no committee' : 'no name');
                if ($committee && $name !== null) {
                    $this->report->review("{$committee->name}: a member without a name (\"{$name}\", ".Normalizer::text($row->designation).') — add by hand.');
                }

                continue;
            }

            $person = $this->memberFor($name, Normalizer::text($row->email));
            if ($person) {
                $matched++;
            } else {
                $manual++;
                $person = $this->manualPerson('committees@person', $row, $name,
                    trim(collect([Normalizer::text($row->designation), Normalizer::text($row->authority)])->filter()->implode(', ')) ?: null,
                    Normalizer::text($row->email), Normalizer::text($row->phone));
            }

            // Academic Council keeps the role in "designation" ("President") and the post there otherwise
            $position = Normalizer::text($row->position) ?: (Str::lower((string) Normalizer::text($row->designation)) === 'president' ? 'President' : null);
            [$role, $label] = $this->role($position);
            $this->assign($row, $person, $committee, $role, $label, Normalizer::text($row->header), (int) $row->serial, (bool) $row->publication_status);
        }
        $this->report->review("{$matched} members matched to Administration, {$manual} kept as manual persons (one per name).");

        if (Schema::connection('legacy')->hasTable('lab_committees')) {
            $this->report->review($this->legacy()->table('lab_committees')->count().' lab committee members wait for the Lab module.');
        }
    }

    /** @return array{0: string, 1: ?string} role key and own title */
    protected function role(?string $position): array
    {
        $word = Str::lower((string) $position);

        return match (true) {
            $word === '' || $word === 'member' => ['member', null],
            $word === 'member secretary' => ['member_secretary', null],
            $word === 'convener' => ['convener', null],
            in_array($word, ['chairman', 'chairperson'], true) => ['chairperson', null],
            str_contains($word, 'chairman') && ! str_contains($word, 'vice'), str_contains($word, 'president'), $word === 'proctor' => ['chairperson', $position],
            default => ['member', $position],   // Vice Chairman, Proctor, External Member …
        };
    }

    protected function assign(object $row, Model $person, Committee $committee, string $role, ?string $label, ?string $note, int $order, bool $public): void
    {
        $this->upsert('committees', $row, Assignment::class, [
            'person_type' => $person->getMorphClass(),
            'person_id' => $person->getKey(),
            'assignable_type' => $committee->getMorphClass(),
            'assignable_id' => $committee->getKey(),
            'role_key' => $role,
            'role_label' => Normalizer::en($label),
            'note' => Normalizer::en($note),
            'sort_order' => max(0, $order),
            'is_public' => $public,
            'is_active' => true,
        ]);
    }
}
