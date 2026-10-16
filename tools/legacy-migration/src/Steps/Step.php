<?php

namespace Legacy\Migration\Steps;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Legacy\Migration\Support\IdMap;
use Legacy\Migration\Support\Normalizer;
use Legacy\Migration\Support\Report;
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

    /** A manual person from a legacy row's own name / designation / email / phone (rows without an admin). */
    protected function rowPerson(string $table, object $row): ?ManualPerson
    {
        return filled($row->name ?? null)
            ? $this->manualPerson($table.'@person', $row, $row->name, $row->designation ?? null, $row->email ?? null, $row->phone ?? null)
            : null;
    }

    protected function manualPerson(string $key, object $row, ?string $name, ?string $designation, ?string $email, ?string $phone): ManualPerson
    {
        $name = Normalizer::text($name);

        return $this->upsert($key, $row, ManualPerson::class, [
            'name' => Normalizer::en($name),
            'designation' => Normalizer::en(Normalizer::text($designation)),
            'email' => Normalizer::text($email),
            'phone' => mb_substr((string) Normalizer::text($phone), 0, 30) ?: null,
            'is_active' => true,
        ], existing: $this->same(ManualPerson::class, ['name->en' => $name]));
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
}
