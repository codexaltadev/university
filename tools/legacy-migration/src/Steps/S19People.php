<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Legacy\Migration\Support\IdMap;
use Modules\Administration\Models\AdministrationCategory;
use Modules\Administration\Models\Member;
use Platform\Assignments\Assignment;
use Platform\Assignments\ManualPerson;

/**
 * S19P People not in Administration (owner 2026-11-01): after every step that names people
 * (S08, S10, S11C, S13, S15T, S18L), office teams, office heads, support contacts and coordinators
 * still kept as manual persons are tagged to their member or become contractual members, so one
 * run from an empty database ends as a second run does.
 */
class S19People extends Step
{
    /** Office-like roles whose people, when Administration does not have them, become contractual members */
    public const STAFF_ROLES = ['team', 'head', 'contact', 'coordinator'];

    /** When no category fits the designation: not in the directory, for review (People › Administration, filter Employment) */
    public const CONTRACTUAL_CATEGORY = 'Contractual Staff';

    /** @var array<string, int>|null designation key => the category most members of it are in (categoryFor) */
    protected ?array $designations = null;

    public static function key(): string
    {
        return 'S19P';
    }

    public static function title(): string
    {
        return 'People not in Administration: tagged or contractual';
    }

    public static function requires(): array
    {
        return ['administration'];
    }

    protected function handle(): void
    {
        $this->members();
        $this->contractualMembers();
        $this->members();   // a committee's "Professor Dr. Mostafa Kamal" is the contractual Registrar just made
        $this->orphans();
    }

    /** Manual persons the migration made that nobody points at any more (their people are members now) */
    protected function orphans(): void
    {
        $type = (new ManualPerson)->getMorphClass();
        $orphans = ManualPerson::query()->withoutGlobalScopes()->whereIn('id', DB::table(IdMap::TABLE)->where('new_type', $type)->select('new_id'))
            ->whereNotExists(fn ($q) => $q->from((new Assignment)->getTable())->where('person_type', $type)->whereColumn('person_id', (new ManualPerson)->qualifyColumn('id')))
            ->get();
        foreach ($orphans as $manual) {
            DB::table(IdMap::TABLE)->where('new_type', $type)->where('new_id', $manual->getKey())->delete();
            $manual->forceDelete();
        }
        if ($orphans->isNotEmpty()) {
            $this->report->review("{$orphans->count()} manual persons nobody is assigned to any more were removed.");
        }
    }

    /**
     * Any manual person who is a member (same email and name, or the one member of that name —
     * memberFor): office teams, contacts, coordinators, committees, labs are tagged to
     * Administration (owner 2026-11-01). Testimonial authors and video speakers keep theirs.
     */
    protected function members(): void
    {
        $type = (new ManualPerson)->getMorphClass();
        $moved = 0;
        foreach (Assignment::withTrashed()->where('person_type', $type)->whereNotIn('role_key', S11Administration::OWN_PEOPLE)->distinct()->pluck('person_id') as $manualId) {
            $manual = ManualPerson::query()->withoutGlobalScopes()->find($manualId);
            $member = $manual ? $this->memberFor($manual->getTranslation('name', 'en', false), $manual->email) : null;
            if ($member === null) {
                continue;
            }
            $moved += Assignment::withTrashed()->where('person_type', $type)->where('person_id', $manualId)->whereNotIn('role_key', S11Administration::OWN_PEOPLE)->count();
            $this->movePerson($manual, $member);   // a rerun's manualPerson() finds the member through the id map
        }
        $this->report->review("{$moved} assignments moved from manual persons to Administration members.");
    }

    /**
     * People of an office team, office head, support contact or coordinator list who are not a
     * member yet (owner 2026-11-01): the one active member whose name holds theirs or the other way
     * round ("Muhammad Mukaddim Ul Islam Anik" is "Mr. Muhammad Mukaddim Ul Islam") is tagged;
     * someone Administration does not have (no member of that name, active or not, and not a
     * teacher) is contractual for now — a member in the category their designation is in
     * (categoryFor), else "Contractual Staff"; not in the directory, shown where assigned. The
     * manual person goes and its id-map rows point at the member, so a rerun of S08 / S10 / S15
     * finds the member. Committee and lab people stay manual: mostly teachers and outsiders.
     */
    protected function contractualMembers(): void
    {
        $type = (new ManualPerson)->getMorphClass();
        $members = Member::query()->withoutGlobalScopes()->with('category')->get();   // active, inactive and trashed
        $people = [
            ...Assignment::withTrashed()->where('person_type', $type)->whereIn('role_key', self::STAFF_ROLES)->distinct()->pluck('person_id')
                ->map(fn ($id) => ManualPerson::query()->withoutGlobalScopes()->find($id))->filter()->all(),
            ...$members->filter(fn (Member $m): bool => $m->employment_type === 'contractual'
                && $m->category?->getTranslation('name', 'en', false) === self::CONTRACTUAL_CATEGORY)->all(),   // an earlier run's, not placed yet
        ];

        $known = [];   // personKey => true: in Administration (any state) or a teacher
        foreach ($members as $member) {
            $known[self::personKey($member->getTranslation('name', 'en', false))] = true;
        }
        $teacher = 'Modules\\FacultyMember\\Models\\FacultyMember';
        foreach (class_exists($teacher) ? $teacher::query()->withoutGlobalScopes()->get() : [] as $faculty) {
            $known[self::personKey($faculty->getTranslation('name', 'en', false))] = true;
        }

        $made = $tagged = $kept = 0;
        $mine = [];   // personKey => member made here ("Mr. X" and "X" are one)
        foreach ($people as $person) {
            $name = $person->getTranslation('name', 'en', false);
            $key = self::personKey($name);
            $same = $this->namesake($name, $members->reject(fn (Member $m): bool => $m->is($person) || $m->employment_type === 'contractual'));
            if ($same && $same->is_active && ! $same->trashed()) {
                $this->movePerson($person, $same);
                $tagged++;

                continue;
            }
            if ($person instanceof Member) {
                continue;
            }
            if ($key === '' || (isset($known[$key]) && ! isset($mine[$key]))) {
                $kept++;

                continue;
            }

            if (! isset($mine[$key])) {
                // An office mailbox another member has (ceoffice@, controller@) is not copied: two members with it would stop memberFor matching by email on the next run
                $email = filter_var($person->email, FILTER_VALIDATE_EMAIL) ?: null;
                if ($email && Member::query()->withoutGlobalScopes()->whereNull('deleted_at')->where('email', $email)->exists()) {
                    $email = null;
                }
                $mine[$key] = Member::query()->create([
                    'category_id' => $this->categoryFor($person->getTranslation('designation', 'en', false)) ?? $this->contractualCategory()->id,
                    'employment_type' => 'contractual',
                    'name' => $person->getTranslations('name'),
                    'designation' => $person->getTranslations('designation'),
                    'email' => $email,
                    'phone' => $person->phone,
                    'is_active' => true,
                ]);
                $known[$key] = true;
                $made++;
                if ($same) {
                    $this->report->review("Contractual member \"{$mine[$key]->name}\" may be the inactive member #{$same->id} \"{$same->name}\": check in People › Administration.");
                }
            }
            $this->movePerson($person, $mine[$key]);
        }

        // Contractual members still in "Contractual Staff" (an earlier run's) go to the category of their designation; an empty one goes
        if ($waiting = AdministrationCategory::query()->where('name->en', self::CONTRACTUAL_CATEGORY)->first()) {
            foreach (Member::query()->withoutGlobalScopes()->where('category_id', $waiting->id)->where('employment_type', 'contractual')->get() as $member) {
                if ($category = $this->categoryFor($member->getTranslation('designation', 'en', false))) {
                    $member->update(['category_id' => $category]);
                } else {
                    $this->report->review("Contractual member \"{$member->name}\" ({$member->designation}): no category fits the designation — in \"".self::CONTRACTUAL_CATEGORY.'".');
                }
            }
            if (! Member::query()->withoutGlobalScopes()->where('category_id', $waiting->id)->exists()) {
                $waiting->forceDelete();
            }
        }
        $this->members = null;   // memberFor sees them now

        $this->report->review("Office teams, contacts, coordinators: {$tagged} people tagged to the member of a fuller or shorter name, {$made} added as contractual members in the category of their designation (not in the directory) — check them in People › Administration, filter Employment.");
        if ($kept > 0) {
            $this->report->review("{$kept} such people stay manual: an inactive member or a teacher has the same name — tag them by hand.");
        }
    }

    /** The one member whose name holds the other's, at least two words ("Md." aside); null when none or several */
    protected function namesake(?string $name, Collection $members): ?Member
    {
        $words = fn (?string $n): array => array_values(array_diff(explode(' ', self::personKey($n)), ['md', 'mohammad', 'mohammed', 'muhammad', '']));
        $a = $words($name);
        if (count($a) < 2) {
            return null;
        }
        $found = $members->filter(function (Member $m) use ($a, $words): bool {
            $b = $words($m->getTranslation('name', 'en', false));

            return count($b) >= 2 && (array_diff($a, $b) === [] || array_diff($b, $a) === []);
        });

        return $found->count() === 1 ? $found->first() : null;
    }

    /**
     * The category for a designation: the one most members with that designation are in
     * ("Assistant Coordination Officer" → Assistant Officer), else the category whose every word is
     * in it ("Assistant Exam Officer" → Assistant Officer, "Office Attendant" → Attendant).
     */
    protected function categoryFor(?string $designation): ?int
    {
        $wanted = self::designationKey($designation);
        if ($wanted === '') {
            return null;
        }

        $this->designations ??= Member::query()->withoutGlobalScopes()->where('employment_type', '!=', 'contractual')->get()
            ->groupBy(fn (Member $m): string => self::designationKey($m->getTranslation('designation', 'en', false)))
            ->map(fn (Collection $same): int => (int) $same->countBy('category_id')->sortDesc()->keys()->first())->all();
        if (isset($this->designations[$wanted])) {
            return $this->designations[$wanted];
        }

        $words = explode(' ', $wanted);
        $best = AdministrationCategory::query()->get()
            ->map(fn (AdministrationCategory $c): array => [$c->id, (string) $c->getTranslation('name', 'en', false)])
            ->reject(fn (array $c): bool => str_contains($c[1], '/') || $c[1] === self::CONTRACTUAL_CATEGORY)   // "Deputy Registrar/Director": by members only
            ->map(fn (array $c): array => [$c[0], explode(' ', self::designationKey($c[1]))])
            ->filter(fn (array $c): bool => $c[1] !== [''] && array_diff($c[1], $words) === [])
            ->sortByDesc(fn (array $c): int => count($c[1]))->first();

        return $best[0] ?? null;
    }

    /** "Asst. Coordination Officer (CIS)" → "assistant coordination officer" */
    public static function designationKey(?string $designation): string
    {
        $d = (string) preg_replace('/\basst\b\.?/u', 'assistant', Str::lower((string) $designation));
        $d = (string) preg_replace(['/\(.*?\)/u', '/[^\pL ]/u'], ' ', $d);

        return trim((string) preg_replace('/\s+/', ' ', $d));
    }

    protected function contractualCategory(): AdministrationCategory
    {
        return AdministrationCategory::query()->where('name->en', self::CONTRACTUAL_CATEGORY)->first()
            ?? AdministrationCategory::query()->create(['name' => ['en' => self::CONTRACTUAL_CATEGORY], 'group' => 'support',
                'is_public' => false, 'is_active' => true, 'sort_order' => (int) AdministrationCategory::query()->max('sort_order') + 1]);
    }
}
