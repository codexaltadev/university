<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * One admission test's results (FR-ADM-05/08): a session's list, imported from a file, with
 * the message shown for each result ({name} = the student). Active = published.
 * Removing a batch removes its results (BRL-ADM-01).
 */
class ResultBatch extends BaseModel
{
    use HasTranslations;

    /** The four results and "no such serial". */
    public const MESSAGES = ['selected', 'not_selected', 'waiting', 'absent', 'not_found'];

    protected $table = 'admission_result_batches';

    protected $fillable = ['name', 'session_label', 'messages', 'imported_rows', 'imported_at', 'is_active'];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return ['messages' => 'array', 'imported_at' => 'datetime', 'imported_rows' => 'integer'];
    }

    protected static function booted(): void
    {
        // A new batch starts with the last batch's messages (DB-03 §15)
        static::creating(function (ResultBatch $batch): void {
            if (blank(array_filter((array) $batch->messages))) {
                $batch->messages = static::query()->latest('id')->first()?->messages;
            }
        });
    }

    public function results(): HasMany
    {
        return $this->hasMany(AdmissionResult::class, 'batch_id');
    }

    /** The message for a result in a language (the default language's when missing), with the name filled in. */
    public function message(string $result, ?string $name = null, ?string $locale = null): ?string
    {
        $texts = (array) (($this->messages ?? [])[$result] ?? []);
        $text = settings()->translate($texts, $locale);

        return filled($text) ? strtr((string) $text, ['{name}' => (string) $name]) : null;
    }
}
