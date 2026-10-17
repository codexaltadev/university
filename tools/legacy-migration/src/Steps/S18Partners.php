<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Legacy\Migration\Support\Normalizer;
use Modules\Lab\Models\Lab;
use Modules\Partners\Models\Partner;
use Platform\Relations\RelationEngine;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * S18P Partners (DB-06 v1.3 §8): `discount_partner_categories` → partners.discount-category (the
 * Font Awesome icon → the nearest Heroicon); `discount_partners` → type "discount" (value from its
 * HTML, details → terms); `mps` → by `mps_categories`: Members → membership, Partners → industry,
 * Supporting Unit / Accreditations → other; `is_international` kept; `lab_facility_id` → shown in
 * that lab (S18L) instead of the main site. Logos wait for S03.
 */
class S18Partners extends Step
{
    /** Font Awesome → Heroicon (the icon field takes Heroicon names) */
    public const ICONS = [
        'graduation-cap' => 'heroicon-o-academic-cap', 'book-medical' => 'heroicon-o-heart', 'coins' => 'heroicon-o-banknotes',
        'building-columns' => 'heroicon-o-building-library', 'building' => 'heroicon-o-building-office', 'medium' => 'heroicon-o-newspaper',
        'researchgate' => 'heroicon-o-beaker', 'lightbulb' => 'heroicon-o-light-bulb', 'tractor' => 'heroicon-o-sun',
        'handshake' => 'heroicon-o-hand-raised', 'ethernet' => 'heroicon-o-squares-2x2',
    ];

    public const MPS_TYPES = [1 => 'membership', 2 => 'industry', 3 => 'other', 4 => 'other'];

    public static function key(): string
    {
        return 'S18P';
    }

    public static function title(): string
    {
        return 'Partners';
    }

    public static function requires(): array
    {
        return ['partners'];
    }

    protected function handle(): void
    {
        $engine = app(RelationEngine::class);
        if (Schema::connection('legacy')->hasTable('discount_partners')) {
            $vocabulary = app(Vocabularies::class)->find(Partner::CATEGORY);
            $terms = [];
            foreach ($this->rows('discount_partner_categories') as $row) {
                $name = Normalizer::text($row->title);
                $icon = self::ICONS[Str::afterLast((string) $row->icon, 'fa-')] ?? null;
                $terms[$row->id] = $this->upsert('discount_partner_categories', $row, Term::class, [
                    'vocabulary_id' => $vocabulary->id, 'name' => Normalizer::en($name), 'icon' => $icon, 'sort_order' => (int) $row->id, 'is_active' => (bool) $row->status,
                ], after: fn (Term $t, bool $created) => $this->keepSlug($t, Str::slug((string) $name), $created),
                    existing: $this->same(Term::class, ['vocabulary_id' => $vocabulary->id, 'name->en' => $name]));
            }
            foreach ($this->rows('discount_partners') as $row) {
                $name = Normalizer::text($row->title);
                $value = Normalizer::text(trim(preg_replace('/\s+/', ' ', strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], ' ', (string) $row->discount_value)))));
                $this->upsert('discount_partners', $row, Partner::class, [
                    'partner_type' => 'discount',
                    'name' => Normalizer::en($name),
                    'website_url' => Normalizer::url($row->website),
                    'discount_value' => Normalizer::en($value),
                    'discount_terms' => Normalizer::en(Normalizer::text(strip_tags((string) $row->details))),
                    'sort_order' => max(0, (int) $row->serial),
                    'is_active' => (bool) $row->status,
                ], after: function (Partner $p) use ($engine, $terms, $row): void {
                    $term = $terms[(int) $row->discount_partner_category_id] ?? null;
                    $p->syncTerms(Partner::CATEGORY, $term ? [$term->id] : []);
                    $engine->sync($p, [], mainSite: true, user: null);
                }, existing: $this->same(Partner::class, ['partner_type' => 'discount', 'name->en' => $name]));
            }
        }

        if (Schema::connection('legacy')->hasTable('mps')) {
            foreach ($this->rows('mps', fn ($q) => $q->whereNull('deleted_at')) as $i => $row) {
                $name = Normalizer::text($row->title);
                $lab = $row->lab_facility_id && class_exists(Lab::class) ? $this->ids->record('lab_facilities', $row->lab_facility_id, Lab::class) : null;
                if ($row->lab_facility_id && $lab === null) {
                    $this->report->review("mps #{$row->id}: its lab is not migrated (run S18L, then this step again) — on the main site for now.");
                }
                $type = self::MPS_TYPES[(int) $row->mps_category_id] ?? 'other';
                $this->upsert('mps', $row, Partner::class, [
                    'partner_type' => $type,
                    'name' => Normalizer::en($name),
                    'website_url' => Normalizer::url($row->url),
                    'is_international' => (bool) $row->is_international,
                    'sort_order' => $i,
                    'is_active' => (bool) $row->publication_status,
                ], after: fn (Partner $p) => $engine->sync($p, $lab ? [$lab->getMorphClass().':'.$lab->getKey()] : [], mainSite: $lab === null, user: null),
                    existing: $this->same(Partner::class, ['partner_type' => $type, 'name->en' => $name]));   // a discount partner of the same name stays apart
            }
            $this->report->review('mps "Accreditations" (4) became partners of type "other": move them to Accreditations if they are accreditations.');
        }
        $this->report->review('Partner logos wait for the files (S03).');
    }
}
