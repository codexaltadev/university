<?php

namespace Modules\Fee\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Modules\Faculty\Models\Faculty;
use Platform\Models\BaseModel;
use Platform\Taxonomy\Term;
use Spatie\Translatable\HasTranslations;

/**
 * A waiver rule (FR-FEE-05, DB-03 §11.2 v1.1): level, fee category, result ranges
 * (SSC / HSC GPA, golden, undergraduate CGPA), the boards, genders, faculties, tuitions
 * and waiver categories it applies to (none chosen = all), and the waiver percent —
 * with a higher "improved" percent when the HSC GPA beats the SSC GPA.
 */
class WaiverRule extends BaseModel
{
    use HasTranslations;

    public const GENDERS = ['male', 'female', 'other'];

    public static string $titleAttribute = 'name';

    protected $table = 'fee_waiver_rules';

    protected $fillable = [
        'name', 'level_term_id', 'fee_category_id', 'ssc_min', 'ssc_max', 'hsc_min', 'hsc_max', 'require_ssc_golden', 'require_hsc_golden',
        'ug_cgpa_min', 'ug_cgpa_max', 'is_international', 'waiver_percent', 'improved_waiver_percent', 'max_waiver_percent', 'message', 'priority', 'is_active',
    ];

    public array $translatable = ['name', 'message'];

    protected function casts(): array
    {
        return [
            'level_term_id' => 'integer', 'fee_category_id' => 'integer',
            'ssc_min' => 'decimal:2', 'ssc_max' => 'decimal:2', 'hsc_min' => 'decimal:2', 'hsc_max' => 'decimal:2',
            'ug_cgpa_min' => 'decimal:2', 'ug_cgpa_max' => 'decimal:2',
            'require_ssc_golden' => 'boolean', 'require_hsc_golden' => 'boolean', 'is_international' => 'boolean',
            'waiver_percent' => 'decimal:2', 'improved_waiver_percent' => 'decimal:2', 'max_waiver_percent' => 'decimal:2',
            'priority' => 'integer',
        ];
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'level_term_id');
    }

    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class);
    }

    public function boards(): BelongsToMany
    {
        return $this->belongsToMany(Board::class, 'fee_waiver_rule_boards', 'rule_id', 'board_id');
    }

    /** In order: the first one names the result ("… under Female quota"). */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(WaiverCategory::class, 'fee_waiver_rule_categories', 'rule_id', 'waiver_category_id')
            ->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function faculties(): BelongsToMany
    {
        return $this->belongsToMany(Faculty::class, 'fee_waiver_rule_faculties', 'rule_id', 'faculty_id');
    }

    public function tuitions(): BelongsToMany
    {
        return $this->belongsToMany(Tuition::class, 'fee_waiver_rule_tuitions', 'rule_id', 'tuition_id');
    }

    /** @return list<string> */
    public function genders(): array
    {
        return DB::table('fee_waiver_rule_genders')->where('rule_id', $this->getKey())->orderBy('gender')->pluck('gender')->all();
    }

    /** @param list<string> $genders */
    public function syncGenders(array $genders): void
    {
        DB::table('fee_waiver_rule_genders')->where('rule_id', $this->getKey())->delete();
        DB::table('fee_waiver_rule_genders')->insert(collect($genders)->intersect(self::GENDERS)->unique()
            ->map(fn (string $g): array => ['rule_id' => $this->getKey(), 'gender' => $g])->values()->all());
    }

    /** Categories in the given order (sort_order = position). @param list<int> $ids */
    public function syncCategories(array $ids): void
    {
        $this->categories()->sync(collect($ids)->values()->mapWithKeys(fn ($id, int $i): array => [(int) $id => ['sort_order' => $i]])->all());
    }
}
