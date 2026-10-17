<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Legacy\Migration\Support\Normalizer;
use Locale;
use Modules\Department\Models\Department;
use Modules\International\Models\Conference;
use Modules\International\Models\Country;
use Modules\International\Models\Institution;
use Modules\International\Models\Visitor;
use Platform\Relations\RelationEngine;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * S18I International (DB-06 v1.3 §6): `countries` → countries with their ISO code found from the
 * name (the old codes were "aus", "bela" …); `international_linkages` → partner institutions
 * (agreement unknown: "other"); `featured_partners` → highlighted on the main site in their order;
 * `international_conferences` + categories; `visitors` → visitors (country matched by name,
 * department → shown there, `is_home` → highlighted). Flags, logos and photos wait for S03.
 */
class S18International extends Step
{
    /** Names the ICU list writes differently */
    public const ALIASES = [
        'brunei darussalam' => 'BN', 'czech republic' => 'CZ', 'congo' => 'CG', 'usa' => 'US', 'united states of america' => 'US', 'uk' => 'GB',
        'england' => 'GB', 'korea' => 'KR', 'south korea' => 'KR', 'republic of korea' => 'KR', 'russia' => 'RU', 'vietnam' => 'VN', 'viet nam' => 'VN',
        'turkey' => 'TR', 'turkiye' => 'TR', 'iran' => 'IR', 'taiwan' => 'TW', 'macedonia' => 'MK', 'laos' => 'LA', 'syria' => 'SY', 'palestine' => 'PS',
        'hong kong' => 'HK', 'macau' => 'MO', 'uae' => 'AE', 'north cyprus' => 'XC', 'kyrgyzstan' => 'KG', 'moldova' => 'MD', 'tanzania' => 'TZ', 'bolivia' => 'BO', 'venezuela' => 'VE',
    ];

    /** @var array<string, string>|null lower-case English name → ISO 3166 alpha-2 */
    protected ?array $codes = null;

    public static function key(): string
    {
        return 'S18I';
    }

    public static function title(): string
    {
        return 'International';
    }

    public static function requires(): array
    {
        return ['international'];
    }

    protected function handle(): void
    {
        if (! Schema::connection('legacy')->hasTable('international_linkages')) {
            $this->report->review('No international_linkages table in the legacy database: nothing to migrate.');

            return;
        }
        $engine = app(RelationEngine::class);
        $countries = [];
        foreach ($this->rows('countries') as $row) {
            $name = (string) Normalizer::text($row->country_name);
            $iso = $this->iso($name);
            if ($iso === null) {
                $iso = 'X'.Str::upper(substr($name, 0, 1));   // a user-assigned code (XA–XZ), never a real country's
                $this->report->review("countries #{$row->id} \"{$name}\": no ISO code found — \"{$iso}\" used, correct it in International › Countries.");
            }
            $countries[$row->id] = $this->upsert('countries', $row, Country::class, [
                'name' => Normalizer::en($name), 'iso2' => $iso, 'is_active' => (bool) $row->publication_status && $row->deleted_at === null,
            ], existing: $this->same(Country::class, ['iso2' => $iso]));
        }

        $featured = $this->legacy()->table('featured_partners')->where('publication_status', 1)->pluck('serial', 'international_linkage_id');
        foreach ($this->rows('international_linkages', fn ($q) => $q->whereNull('deleted_at')) as $i => $row) {
            $country = $countries[(int) $row->country_id] ?? null;
            if ($country === null) {
                $this->report->skipped('international_linkages', 'no country');

                continue;
            }
            $name = Normalizer::text($row->name);
            $this->upsert('international_linkages', $row, Institution::class, [
                'name' => Normalizer::en($name),
                'country_id' => $country->id,
                'website_url' => self::web($row->university_url),
                'agreement_type' => 'other',
                'sort_order' => $i,
                'is_active' => (bool) $row->publication_status,
            ], after: fn (Institution $p) => $engine->sync($p, [], mainSite: true,
                highlights: $featured->has($row->id) ? ['site:0' => ['order' => (int) $featured[$row->id]]] : [], user: null),
                existing: $this->same(Institution::class, ['country_id' => $country->id, 'name->en' => $name]));
        }
        $this->report->review('Partner institutions: the old list had no agreement kind, dates or MoU — all are "other"; set them over time (or import a sheet).');

        $vocabulary = app(Vocabularies::class)->find(Conference::CATEGORY);
        $terms = [];
        foreach ($this->rows('international_conference_categories') as $row) {
            $name = Normalizer::text($row->title);
            $terms[$row->id] = $this->upsert('international_conference_categories', $row, Term::class, [
                'vocabulary_id' => $vocabulary->id, 'name' => Normalizer::en($name), 'sort_order' => (int) $row->id, 'is_active' => (bool) $row->publication_status,
            ], after: fn (Term $t, bool $created) => $this->keepSlug($t, $row->slug, $created),
                existing: $this->same(Term::class, ['vocabulary_id' => $vocabulary->id, 'name->en' => $name]));
        }
        foreach ($this->rows('international_conferences', fn ($q) => $q->whereNull('deleted_at')) as $row) {
            $title = (string) Normalizer::text($row->conference_title);
            $year = preg_match('/\b(19|20)\d{2}\b/', $title, $m) ? (int) $m[0] : null;
            $this->upsert('international_conferences', $row, Conference::class, [
                'title' => Normalizer::en($title),
                'body' => Normalizer::en(Normalizer::html($row->conference_detail)),
                'starts_on' => $year ? "{$year}-01-01" : substr((string) $row->created_at, 0, 10),
                'is_active' => (bool) $row->publication_status,
            ], after: function (Conference $c, bool $created) use ($engine, $terms, $row): void {
                $this->keepSlug($c, preg_match('/^\d+$/', (string) $row->slug) ? null : $row->slug, $created);
                $term = $terms[(int) $row->international_conference_category_id] ?? null;
                $c->syncTerms(Conference::CATEGORY, $term ? [$term->id] : []);
                $engine->sync($c, [], mainSite: true, user: null);
            }, existing: $this->same(Conference::class, ['title->en' => $title]));
        }
        $this->report->review('Conferences: the old system had no dates — the year in the title (or the day entered) was used; set the real dates and venue.');

        $byName = collect($countries)->mapWithKeys(fn (Country $c): array => [mb_strtolower((string) $c->name) => $c]);
        foreach ($this->rows('visitors', fn ($q) => $q->whereNull('deleted_at')) as $row) {
            $name = Normalizer::text($row->name);
            if ($name === null) {
                $this->report->skipped('visitors', 'no name');

                continue;
            }
            $countryName = mb_strtolower((string) Normalizer::text($row->country));
            $country = $countryName !== '' ? ($byName[$countryName] ?? Country::query()->where('iso2', $this->iso($countryName) ?? '--')->first()) : null;
            $department = $row->department_id && class_exists(Department::class) ? $this->ids->record('departments', $row->department_id, Department::class) : null;
            $place = $department ? $department->getMorphClass().':'.$department->getKey() : null;
            $this->upsert('visitors', $row, Visitor::class, [
                'name' => Normalizer::en($name),
                'designation' => Normalizer::en(Normalizer::text($row->designation)),
                'organization' => Normalizer::en(Normalizer::text($row->company)),
                'country_id' => $country?->id,
                'visited_on' => $row->date ?: (substr((string) ($row->created_at ?: $row->updated_at), 0, 10) ?: '2018-01-01'),   // a few have no day at all
                'body' => Normalizer::en(Normalizer::html($row->content)),
                'is_external' => (bool) $row->is_external,
                'is_active' => (bool) $row->publication_status,
            ], after: fn (Visitor $v) => $engine->sync($v, $place ? [$place] : [], mainSite: $place === null || (bool) $row->is_home,
                highlights: $row->is_home ? ['site:0' => ['order' => max(0, (int) $row->serial)]] : [], user: null));
        }
        $this->report->review('Country flags, partner logos and visitor photos wait for the files (S03).');
    }

    /** "ttps://…" and "http:// www…" mended; anything else that is no web address ("http://Guizhou University") dropped. */
    public static function web(?string $value): ?string
    {
        $value = preg_replace(['#^ttps://#i', '#^(https?://)\s+#i'], ['https://', '$1'], trim((string) $value));

        return preg_match('#^https?://[^\s/]+\.[^\s]+$#i', (string) $value) ? $value : null;
    }

    protected function iso(string $name): ?string
    {
        if ($this->codes === null) {
            $this->codes = [];
            foreach (range('A', 'Z') as $a) {
                foreach (range('A', 'Z') as $b) {
                    $display = Locale::getDisplayRegion('und_'.$a.$b, 'en');
                    if ($display !== '' && $display !== $a.$b && ! preg_match('/^Unknown/i', $display)) {
                        $this->codes[mb_strtolower($display)] ??= $a.$b;
                    }
                }
            }
        }
        $key = mb_strtolower(trim($name));

        return self::ALIASES[$key] ?? $this->codes[$key] ?? null;
    }
}
