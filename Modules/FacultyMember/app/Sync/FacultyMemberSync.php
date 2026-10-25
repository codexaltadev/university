<?php

namespace Modules\FacultyMember\Sync;

use App\Services\FmsApiClient;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Department\Models\Department;
use Modules\FacultyMember\Models\FacultyMember;
use Platform\Localization\Languages;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * faculty-member:sync (FR-FM-08, DB-03 §8.3): the source's records into the read-only copy.
 * Matched by the source ID: new ones added, changed ones updated, returned ones restored with
 * their assignments, missing ones soft deleted. An empty or unreadable source stops the sync
 * before anything is written (BRL-FM-05). This site's own columns are never touched (BRL-FM-03).
 */
class FacultyMemberSync
{
    public function __construct(protected RecordMapper $mapper) {}

    /** The source chosen in Settings › Faculty Members. */
    public static function configuredSource(): FacultyMemberSource
    {
        if (settings('faculty-member.source', 'json') === 'fms') {
            return new FmsSource(app(FmsApiClient::class));
        }
        if (settings('faculty-member.source', 'json') === 'api') {
            $url = (string) settings('faculty-member.api_url');
            if (blank($url)) {
                throw new SourceUnavailable(__('facultymember::facultymember.sync.no_api_url'));
            }

            return new ApiSource($url, settings('faculty-member.api_token'));
        }

        $path = (string) settings('faculty-member.json_path');

        return new JsonFileSource($path === '' ? JsonFileSource::samplePath() : (str_starts_with($path, '/') ? $path : base_path($path)));
    }

    public function run(?FacultyMemberSource $source = null): SyncResult
    {
        try {
            $source ??= static::configuredSource();
            $result = new SyncResult($source->describe());
            $records = $source->records();
        } catch (SourceUnavailable $e) {
            return $this->remember(new SyncResult(isset($source) ? $source->describe() : '', $e->getMessage()));
        }

        $result->total = count($records);
        $mapped = [];
        $seen = [];
        foreach ($records as $i => $raw) {
            try {
                $record = $this->mapper->map($raw);
                $mapped[$record['external_ref']] = $record;   // a repeated ID: the last one wins
            } catch (InvalidArgumentException $e) {
                $result->errors[] = __('facultymember::facultymember.sync.record', ['line' => $i + 1, 'why' => $e->getMessage()]);
                if (is_scalar($raw['id'] ?? null)) {
                    $seen[] = (string) $raw['id'];   // a broken record does not remove the teacher
                }
            }
        }
        if ($mapped === []) {
            $result->failure = __('facultymember::facultymember.sync.nothing_usable');

            return $this->remember($result);
        }

        DB::transaction(function () use ($mapped, $result, &$seen): void {
            $all = Department::query()->withoutGlobalScopes()->whereNull('deleted_at')->get(['id', 'short_name', 'name']);
            $departments = $all->mapWithKeys(fn (Department $d): array => [mb_strtolower((string) $d->short_name) => (int) $d->id])->all();
            // A source whose code differs from ours (FMS "IE" = our "DE") also gives the name: matched by it next
            $names = $all->mapWithKeys(fn (Department $d): array => [mb_strtolower(trim((string) $d->name)) => (int) $d->id])->all();
            $designations = $this->designationTerms();
            // IDs as strings: a numeric one ("2125", FMS) turns into an int as an array key, and MySQL would compare it as a number
            $refs = array_map('strval', array_keys($mapped));
            $existing = FacultyMember::query()->withoutGlobalScopes()->whereIn('external_ref', $refs)->get()->keyBy('external_ref');
            $unmatched = [];

            foreach ($mapped as $ref => $record) {
                $ref = (string) $ref;
                $seen[] = $ref;
                $attributes = $record['attributes'];
                $attributes['department_id'] = $this->department($record['department'], $departments, $unmatched, $record['department_name'] ?? null, $names);
                $attributes['designation_term_id'] = $record['designation'] !== null ? $this->designation($record['designation'], $designations) : null;
                $others = collect($record['other_departments'])->map(fn (string $d) => $this->department($d, $departments, $unmatched))
                    ->filter(fn (?int $id) => $id !== null && $id !== $attributes['department_id'])->unique()->values()->all();
                $hash = sha1(json_encode([$attributes, $others]));

                /** @var FacultyMember|null $member */
                $member = $existing->get($ref);
                if ($member && ! $member->trashed() && $member->source_hash === $hash) {
                    $result->unchanged++;

                    continue;
                }

                $wasTrashed = $member?->trashed() ?? false;
                $member ??= new FacultyMember;
                if ($wasTrashed) {
                    $member->restore();   // back on the faculty site: back here, assignments and all
                }
                $isNew = ! $member->exists;
                $member->fill($attributes + ['source_hash' => $hash, 'synced_at' => now()]);
                if ($member->employee_id !== null && FacultyMember::query()->withoutGlobalScopes()->whereNull('deleted_at')
                    ->where('employee_id', $member->employee_id)->where('external_ref', '!=', $ref)->exists()) {
                    $result->errors[] = __('facultymember::facultymember.sync.employee_id', ['id' => $member->employee_id, 'ref' => $ref]);   // BRL-FM-01
                    $member->employee_id = null;
                }
                $member->save();
                $member->departments()->sync($others);

                $isNew ? $result->created++ : ($wasTrashed ? $result->restored++ : $result->updated++);
            }

            // Not on the faculty site any more: gone everywhere (restored if they come back)
            FacultyMember::query()->withoutGlobalScopes()->whereNull('deleted_at')->whereNotIn('external_ref', array_unique($seen))
                ->get()->each(function (FacultyMember $member) use ($result): void {
                    $member->delete();
                    $result->removed++;
                });

            FacultyMember::query()->withoutGlobalScopes()->whereIn('external_ref', $refs)->update(['synced_at' => now()]);
            $result->unmatchedDepartments = array_values(array_unique($unmatched));
        });

        return $this->remember($result);
    }

    /** The last result, for the admin list (Settings faculty-member.last_sync, private). */
    public static function lastResult(): ?array
    {
        $last = settings('faculty-member.last_sync');

        return is_array($last) ? $last : null;
    }

    protected function remember(SyncResult $result): SyncResult
    {
        settings()->save('faculty-member', ['last_sync' => $result->toArray()], private: ['last_sync']);

        return $result;
    }

    /**
     * @param  array<string, int>  $departments  short name (lower case) => id
     * @param  array<string, int>  $names  name (lower case) => id, when the source gives the name too
     */
    protected function department(?string $short, array $departments, array &$unmatched, ?string $name = null, array $names = []): ?int
    {
        if ($short === null) {
            return null;
        }
        $id = $departments[mb_strtolower($short)] ?? ($name !== null ? $names[mb_strtolower(trim($name))] ?? null : null);
        if ($id === null) {
            $unmatched[] = $short;   // BRL-FM-04
        }

        return $id;
    }

    /** @return array<string, int> name in the default language (lower case) => term id */
    protected function designationTerms(): array
    {
        $vocabulary = app(Vocabularies::class)->find(FacultyMember::DESIGNATION);
        $default = app(Languages::class)->default();

        return Term::query()->where('vocabulary_id', $vocabulary->id)->get()
            ->mapWithKeys(fn (Term $t): array => [mb_strtolower((string) $t->getTranslation('name', $default, true)) => $t->id])->all();
    }

    /** A designation the vocabulary does not have yet becomes a term (its place in the ladder is set in Taxonomy, FR-FM-06). */
    protected function designation(string $name, array &$terms): int
    {
        $key = mb_strtolower($name);
        if (! isset($terms[$key])) {
            $term = Term::query()->create([
                'vocabulary_id' => app(Vocabularies::class)->find(FacultyMember::DESIGNATION)->id,
                'name' => [app(Languages::class)->default() => $name],
            ]);
            $terms[$key] = $term->id;
        }

        return $terms[$key];
    }
}
