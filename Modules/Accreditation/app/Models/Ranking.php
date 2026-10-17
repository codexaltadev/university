<?php

namespace Modules\Accreditation\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Accreditation\Database\Factories\RankingFactory;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\IsAttachable;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * A ranking result (M-34, DB-06 v1.3 §5, SRS Services FR-ACR-03/04): one agency (agency_key groups
 * the years = the trend), one year, overall / subject / region; logo and badge from the Media Library.
 */
class Ranking extends BaseModel implements Scopeable
{
    use AppliesUserScope;

    /** @use HasFactory<RankingFactory> */
    use HasFactory;

    use HasMediaUsage;      // logo, badge
    use HasSortOrder;
    use HasTranslations;
    use IsAttachable;

    public const PATH = 'rankings';

    public const SCOPES = ['overall', 'subject', 'region'];

    public static string $titleAttribute = 'agency_name';

    protected $table = 'accreditation_rankings';

    protected $fillable = ['agency_key', 'agency_name', 'year', 'scope', 'subject', 'subject_key', 'rank_label', 'rank_value', 'summary', 'body', 'ranking_url', 'sort_order', 'is_active'];

    protected $attributes = ['scope' => 'overall', 'subject_key' => ''];

    public array $translatable = ['agency_name', 'subject', 'summary', 'body'];

    protected function casts(): array
    {
        return ['year' => 'integer', 'rank_value' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (Ranking $r): void {
            $r->agency_key = Str::slug((string) ($r->agency_key ?: $r->getTranslation('agency_name', config('app.fallback_locale', 'en'), true)));
            if ($r->agency_key === '') {
                throw ValidationException::withMessages(['agency_key' => __('accreditation::accreditation.errors.agency')]);
            }
            if (! in_array($r->scope, self::SCOPES, true)) {
                throw ValidationException::withMessages(['scope' => __('accreditation::accreditation.errors.scope')]);
            }
            if ($r->year < 1900 || $r->year > (int) now()->year + 1) {
                throw ValidationException::withMessages(['year' => __('accreditation::accreditation.errors.year')]);
            }
            if (filled($r->ranking_url) && ! preg_match('#^https?://\S+$#i', (string) $r->ranking_url)) {
                throw ValidationException::withMessages(['ranking_url' => __('accreditation::accreditation.errors.url')]);
            }
            $subject = $r->scope === 'overall' ? '' : (string) collect((array) $r->getTranslations('subject'))->first();
            $r->subject_key = Str::limit(Str::slug($subject), 100, '');
            $r->rank_value ??= self::valueOf((string) $r->rank_label);
            $duplicate = self::query()->whereKeyNot($r->getKey())->where(['agency_key' => $r->agency_key, 'year' => $r->year, 'scope' => $r->scope, 'subject_key' => $r->subject_key])->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['year' => __('accreditation::accreditation.errors.duplicate')]);
            }
        });
    }

    /** "801–1000" → 801, "#45" → 45, "Top 500" → 500: the number the trend sorts by. */
    public static function valueOf(string $label): ?int
    {
        return preg_match('/\d+/', str_replace(',', '', $label), $m) ? (int) $m[0] : null;
    }

    public function scopeNewest(Builder $query): Builder
    {
        return $query->reorder()->orderByDesc($this->qualifyColumn('year'))->orderBy($this->qualifyColumn('sort_order'))->orderBy($this->qualifyColumn('rank_value'));
    }

    public function label(): string
    {
        return trim($this->agency_name.($this->scope !== 'overall' && $this->subject ? ' — '.$this->subject : ''));
    }

    public function url(?string $locale = null): string
    {
        return '/'.self::PATH.'#'.$this->agency_key;
    }

    protected static function newFactory(): RankingFactory
    {
        return RankingFactory::new();
    }
}
