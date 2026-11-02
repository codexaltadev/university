<?php

namespace Legacy\Migration\Steps;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Legacy\Migration\Support\IdMap;
use Legacy\Migration\Support\Normalizer;
use Legacy\Migration\Support\Report;
use Modules\Seo\Support\Redirects;
use Platform\Assignments\Assignment;
use Platform\Assignments\ManualPerson;
use Platform\Localization\Languages;

/**
 * One step of the Data Migration Plan §5 (S00 … S25). Reads only from the legacy
 * connection (M-1), writes only through the modules' models (M-4), and can run any
 * number of times: rows already migrated are updated, not created again (M-3).
 */
abstract class Step
{
    protected Report $report;

    /** @var Collection<int, object>|null legacy admins by id (adminPerson) */
    protected ?Collection $admins = null;

    /** @var array{email: array<string, Model|null>, name: array<string, Model|null>}|null active members (memberFor; null = several) */
    protected ?array $members = null;

    public function __construct(protected IdMap $ids) {}

    /** "S04" */
    abstract public static function key(): string;

    abstract public static function title(): string;

    /** Modules (slugs) that must be active, e.g. ['faculty', 'department']. */
    abstract public static function requires(): array;

    abstract protected function handle(): void;

    public function run(): Report
    {
        $this->report = new Report(static::key(), static::title());
        $this->handle();

        return $this->report;
    }

    protected function legacy(): Connection
    {
        return DB::connection('legacy');
    }

    /** Rows of a legacy table, counted for the report with COUNT(*) (M-7). */
    protected function rows(string $table, ?\Closure $query = null): Collection
    {
        $this->report->source($table, (int) $this->legacy()->table($table)->count());
        $builder = $this->legacy()->table($table)->orderBy('id');

        return ($query ? $query($builder) : $builder)->get();
    }

    /**
     * Create or update the record of a legacy row and remember the mapping.
     *
     * @template T of Model
     *
     * @param  class-string<T>  $model
     * @param  array<string, mixed>  $attributes
     * @param  \Closure(T, bool): void|null  $after  runs after save with "was created"
     * @param  \Closure(list<int>): ?T|null  $existing  a record entered by hand before the first run (same short name …) is taken over, not duplicated; gets the ids other rows already own
     * @return T
     */
    protected function upsert(string $table, object $row, string $model, array $attributes, ?\Closure $after = null, ?\Closure $existing = null): Model
    {
        $record = $this->ids->record($table, $row->id, $model);

        // A natural-key match must not be owned by another legacy row of this table (two equal titles stay two)
        if ($record === null && $existing) {
            $found = $existing($this->ids->ownedIds($table, (new $model)->getMorphClass()));
            $record = $found && ! $this->ids->owns($table, $found) ? $found : null;
        }
        $created = $record === null;

        $record ??= new $model;
        $record->forceFill($attributes);

        if (in_array(SoftDeletes::class, class_uses_recursive($record), true) && $record->trashed()) {
            $record->restore();
        }

        $record->save();
        $this->ids->remember($table, $row->id, $record);
        $this->report->saved($table, $created);

        if ($after) {
            $after($record, $created);
        }

        return $record;
    }

    /**
     * Finder for the same record by a natural key, used when the id map has no row
     * (first run over hand-made data, a lost map): never a duplicate.
     *
     * @param  class-string<Model>  $model
     * @param  array<string, mixed>  $where  column => value; "column->en" for a translation
     */
    protected function same(string $model, array $where): \Closure
    {
        return fn (array $taken = []): ?Model => $model::query()->withoutGlobalScopes()->whereNull((new $model)->qualifyColumn('deleted_at'))
            ->where(fn ($q) => collect($where)->each(fn ($value, string $column) => $value === null ? $q->whereNull($column) : $q->where($column, $value)))
            ->whereKeyNot($taken)
            ->orderBy((new $model)->getKeyName())
            ->first();
    }

    /**
     * The person for a legacy admin (DB-04 v1.1 §11.1): their Administration member once S11 has
     * run, else one manual person per admin, shared by every step (admission contacts, office
     * team, support contacts …). Null when the admin does not exist.
     */
    protected function adminPerson(int|string|null $adminId): ?Model
    {
        if (blank($adminId) || (int) $adminId === 0) {
            return null;
        }

        $member = 'Modules\\Administration\\Models\\Member';
        if (class_exists($member) && ($found = $this->ids->record('admins', $adminId, $member))) {
            return $found;
        }

        $this->admins ??= $this->legacy()->table('admins')->get()->keyBy('id');
        $admin = $this->admins->get((int) $adminId);
        if ($admin === null) {
            return null;
        }

        return $this->manualPerson('admins@manual', $admin, $admin->name, $admin->designation, $admin->email, $admin->phone);
    }

    /**
     * The person of a legacy row's own name / designation / email / phone (rows without an admin):
     * their Administration member when there is one (memberFor), else a manual person.
     */
    protected function rowPerson(string $table, object $row): ?Model
    {
        return filled($row->name ?? null)
            ? $this->memberFor($row->name, $row->email ?? null)
                ?? $this->manualPerson($table.'@person', $row, $row->name, $row->designation ?? null, $row->email ?? null, $row->phone ?? null)
            : null;
    }

    /**
     * The Administration member a person named elsewhere is (owner 2026-11-01: office teams,
     * contacts, coordinators, committees are tagged to Administration, not copied): the active
     * member with that email when the names agree ("Md." aside), else the one active member with
     * that name (titles and dots aside; a name two members share matches no one). Null before S11.
     */
    protected function memberFor(?string $name, ?string $email): ?Model
    {
        $class = 'Modules\\Administration\\Models\\Member';
        if (! class_exists($class)) {
            return null;
        }
        if ($this->members === null) {
            $this->members = ['email' => [], 'name' => []];
            foreach ($class::query()->withoutGlobalScopes()->whereNull('deleted_at')->where('is_active', true)->get() as $member) {
                foreach (['email' => Str::lower(trim((string) $member->email)), 'name' => self::personKey($member->getTranslation('name', 'en', false))] as $by => $key) {
                    if ($key !== '') {
                        $this->members[$by][$key] = array_key_exists($key, $this->members[$by]) ? null : $member;
                    }
                }
            }
        }

        // an office mailbox (breoffice@, cseoffice3@) is shared: the email counts only when the names agree too
        $byEmail = filled($email) ? $this->members['email'][Str::lower(trim($email))] ?? null : null;
        $words = fn (?string $n): array => array_diff(explode(' ', self::personKey($n)), ['md', 'mohammad', 'mohammed', 'muhammad', '']);
        if ($byEmail !== null) {
            [$a, $b] = [$words($name), $words($byEmail->getTranslation('name', 'en', false))];
            if ($a !== [] && $b !== [] && (array_diff($a, $b) === [] || array_diff($b, $a) === [])) {
                return $byEmail;
            }
        }

        return $this->members['name'][self::personKey($name)] ?? null;
    }

    /** "Dr. Md. Rahim," → "md rahim" */
    public static function personKey(?string $name): string
    {
        $name = Str::lower(trim((string) preg_replace('/\s+/', ' ', (string) $name)));
        for ($i = 0; $i < 3; $i++) {
            $name = (string) preg_replace('/^(mr|mrs|ms|miss|dr|prof|professor|engr|engineer)\.?\s+/i', '', $name);
        }

        return trim((string) preg_replace('/[^\pL\pN ]/u', '', $name));
    }

    /** The row's manual person — or the member S11 turned it into (contractual, a fuller name), kept on reruns */
    protected function manualPerson(string $key, object $row, ?string $name, ?string $designation, ?string $email, ?string $phone): Model
    {
        $member = 'Modules\\Administration\\Models\\Member';
        if (class_exists($member) && ($found = $this->ids->record($key, $row->id, $member))) {
            return $found;
        }
        $name = Normalizer::text($name);

        return $this->upsert($key, $row, ManualPerson::class, [
            'name' => Normalizer::en($name),
            'designation' => Normalizer::en(Normalizer::text($designation)),
            'email' => Normalizer::text($email),
            'phone' => mb_substr((string) Normalizer::text($phone), 0, 30) ?: null,
            'is_active' => true,
        ], existing: $this->same(ManualPerson::class, ['name->en' => $name]));
    }

    /** Assignments and id-map rows of a manual person (or a member made earlier) → the member; the old one goes */
    protected function movePerson(Model $from, Model $to): void
    {
        if ($from->is($to)) {
            return;
        }
        Assignment::withTrashed()->where('person_type', $from->getMorphClass())->where('person_id', $from->getKey())->whereNotIn('role_key', S11Administration::OWN_PEOPLE)
            ->update(['person_type' => $to->getMorphClass(), 'person_id' => $to->getKey()]);
        if (! Assignment::withTrashed()->where('person_type', $from->getMorphClass())->where('person_id', $from->getKey())->exists()) {
            DB::table(IdMap::TABLE)->where('new_type', $from->getMorphClass())->where('new_id', $from->getKey())
                ->update(['new_type' => $to->getMorphClass(), 'new_id' => $to->getKey()]);   // a rerun's manualPerson() finds the member
            $from->forceDelete();
        }
    }

    /**
     * Give a newly migrated record the old web address and drop the one made from its name,
     * so old links keep working and no extra history appears.
     */
    protected function keepSlug(Model $record, ?string $slug, bool $created): void
    {
        if (blank($slug) || ! method_exists($record, 'changeSlug')) {
            return;
        }

        foreach (app(Languages::class)->codes() as $locale) {   // the same address in every language: /bn/faculty/fsit
            $record->changeSlug($locale, $slug);
        }

        if ($created) {
            $record->slugs()->where('is_current', false)->delete();
        }
    }

    /**
     * DIU's old address (/events-detail/669, /noticeboard) → a record's page here (following the
     * record, P-15) or a path, 301, as a Site › Redirects row — only when the SEO module is installed.
     */
    protected function oldAddress(string $path, Model|string $to): void
    {
        $class = 'Modules\\Seo\\Models\\Redirect';
        if (! class_exists($class)) {
            return;
        }
        $redirect = $class::query()->firstOrNew(['source_hash' => Redirects::hash(Redirects::normalize($path))]);
        $redirect->fill(['source_path' => $path, 'status_code' => 301, 'source' => 'import', 'is_active' => true, ...($to instanceof Model
            ? ['destination_url' => null, 'target_type' => $to->getMorphClass(), 'target_id' => $to->getKey()]
            : ['destination_url' => $to, 'target_type' => null, 'target_id' => null])])->save();
    }
}
