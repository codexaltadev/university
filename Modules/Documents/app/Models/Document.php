<?php

namespace Modules\Documents\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Documents\Database\Factories\DocumentFactory;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasActiveAt;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSeo;
use Platform\Concerns\HasSlug;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\HasTaxonomy;
use Platform\Concerns\IsAttachable;
use Platform\Concerns\IsSearchable;
use Platform\Context\MicrositeResolver;
use Platform\Contracts\Scopeable;
use Platform\Media\MediaItem;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * A document to read or download (M-27, DB-05 §12 v1.9, SRS Content FR-DOC-01..08): form,
 * policy (version, approval date, older versions superseded), prospectus (session, program),
 * guidebook, charter, report. The file is a Media Library item, or an outside address; a PDF can
 * be read as a flipbook, read-only if wanted; private ones only for signed-in users.
 */
class Document extends BaseModel implements Scopeable
{
    use AppliesUserScope;
    use HasActiveAt;

    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    use HasMediaUsage;      // file, thumbnail
    use HasSeo;
    use HasSlug;            // /downloads/{slug}
    use HasSortOrder;
    use HasTaxonomy;        // documents.category
    use HasTranslations;
    use IsAttachable;
    use IsSearchable;

    public const PATH = 'downloads';

    public const CATEGORY = 'documents.category';

    public const TYPES = ['form', 'policy', 'prospectus', 'guidebook', 'charter', 'report', 'other'];

    public static string $titleAttribute = 'title';

    protected string $slugSource = 'title';

    protected $table = 'documents_documents';

    protected $fillable = [
        'document_type', 'title', 'description', 'external_url', 'version_label', 'approved_on', 'effective_on', 'session_label',
        'program_id', 'superseded_by_id', 'show_as_flipbook', 'allow_download', 'is_private', 'sort_order', 'active_at', 'is_active',
    ];

    public array $translatable = ['title', 'description'];

    protected $attributes = ['document_type' => 'other', 'show_as_flipbook' => false, 'allow_download' => true, 'is_private' => false, 'download_count' => 0];

    protected function casts(): array
    {
        return [
            'approved_on' => 'date', 'effective_on' => 'date', 'show_as_flipbook' => 'boolean', 'allow_download' => 'boolean',
            'is_private' => 'boolean', 'download_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Document $document): void {
            if (! in_array($document->document_type, self::TYPES, true)) {
                throw ValidationException::withMessages(['document_type' => __('documents::documents.errors.type')]);
            }
            if ($document->superseded_by_id !== null && (int) $document->superseded_by_id === (int) $document->getKey()) {
                throw ValidationException::withMessages(['superseded_by_id' => __('documents::documents.errors.self')]);
            }
        });
        static::creating(function (Document $document): void {
            $document->active_at ??= now();
        });
    }

    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'superseded_by_id');
    }

    /** Older versions that this one replaced (FR-DOC-05). */
    public function olderVersions(): HasMany
    {
        return $this->hasMany(self::class, 'superseded_by_id')->latest('approved_on')->latest('id');
    }

    /** The current documents: not replaced by a newer version. */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereNull($this->qualifyColumn('superseded_by_id'));
    }

    /** Signed-in users see private documents too (FR-DOC-07). */
    public function scopeForVisitor(Builder $query, bool $signedIn): Builder
    {
        return $signedIn ? $query : $query->where($this->qualifyColumn('is_private'), false);
    }

    public function scopeNewest(Builder $query): Builder
    {
        return $query->reorder()->orderByDesc($this->qualifyColumn('active_at'))->orderByDesc($this->getQualifiedKeyName());
    }

    public function fileItem(): ?MediaItem
    {
        return $this->firstMedia('file');
    }

    public function isPdf(): bool
    {
        return $this->fileItem()?->mime_type === 'application/pdf';
    }

    /** The flipbook is shown for a PDF only. */
    public function flipbook(): bool
    {
        return $this->show_as_flipbook && $this->isPdf();
    }

    /** "PDF · 2.4 MB" */
    public function fileFacts(): ?string
    {
        $item = $this->fileItem();
        if ($item === null) {
            return filled($this->external_url) ? parse_url((string) $this->external_url, PHP_URL_HOST) : null;
        }
        $size = (int) $item->size_bytes;
        $human = $size >= 1048576 ? number_format($size / 1048576, 1).' MB' : max(1, (int) round($size / 1024)).' KB';

        return strtoupper((string) pathinfo((string) $item->fileName(), PATHINFO_EXTENSION)).' · '.$human;
    }

    public function program(): ?Model
    {
        $program = 'Modules\\Program\\Models\\Program';

        return $this->program_id && class_exists($program) ? $program::query()->find($this->program_id) : null;
    }

    /** One more download, without touching updated_at or the activity log (FR-DOC-06). */
    public function countDownload(): void
    {
        DB::table($this->getTable())->where('id', $this->getKey())->increment('download_count');
    }

    /** /downloads/{slug} on the main site; one only shown in entities lives under the first one's microsite. */
    public function url(?string $locale = null): string
    {
        $slug = $this->slug($locale) ?? (string) $this->getKey();
        if (! $this->isOnMainSite()) {
            foreach ($this->targetKeys() as $key) {
                [$type, $id] = explode(':', $key, 2);
                $model = Relation::getMorphedModel($type);
                $entity = $model ? $model::query()->withoutGlobalScopes()->find($id) : null;
                if ($entity && ($base = app(MicrositeResolver::class)->urlFor($entity, $locale))) {
                    return rtrim($base, '/').'/'.self::PATH.'/'.$slug;
                }
            }
        }

        return '/'.self::PATH.'/'.$slug;
    }

    protected function searchBodyAttributes(): array
    {
        return ['description'];
    }

    protected function jsonLd(string $locale): array
    {
        return array_filter([
            '@type' => 'DigitalDocument',
            'name' => $this->getTranslation('title', $locale, true),
            'description' => $this->getTranslation('description', $locale, true) ?: null,
            'encodingFormat' => $this->fileItem()?->mime_type,
        ]);
    }

    protected static function newFactory(): DocumentFactory
    {
        return DocumentFactory::new();
    }
}
