<?php

namespace Modules\Gallery\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
use Modules\Gallery\Support\VideoLink;
use Platform\Assignments\PersonView;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\IsAssignable;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * A YouTube / Vimeo / Facebook video of an album (DB-05 §7.2 v1.6, FR-GAL-02/04): not a file,
 * so not in the Media Library. Provider and id come from the link (embed, thumbnail); episode
 * and speaker (assignment role "speaker") for a series.
 */
class Video extends BaseModel
{
    use HasSortOrder;
    use HasTranslations;
    use IsAssignable;   // the speaker

    public const ROLE = 'speaker';

    public static string $titleAttribute = 'title';

    protected $table = 'gallery_videos';

    protected $fillable = ['album_id', 'url', 'title', 'episode_no', 'duration_seconds', 'sort_order', 'is_active'];

    public array $translatable = ['title'];

    protected function casts(): array
    {
        return ['episode_no' => 'integer', 'duration_seconds' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (Video $video): void {
            $link = VideoLink::parse((string) $video->url);
            if ($link === null) {
                throw ValidationException::withMessages(['url' => __('gallery::gallery.errors.video_url')]);
            }
            $video->provider = $link['provider'];
            $video->provider_id = $link['id'];
        });
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class, 'album_id');
    }

    public function scopeShown(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('is_active'), true);
    }

    public function embedUrl(): ?string
    {
        return VideoLink::embed($this->provider, $this->provider_id, $this->url);
    }

    public function thumbnailUrl(): ?string
    {
        return VideoLink::thumbnail($this->provider, $this->provider_id);
    }

    public function speaker(): ?PersonView
    {
        return $this->people(self::ROLE)->first();
    }

    /** "1:05:30", "12:04" */
    public function duration(): ?string
    {
        if (! $this->duration_seconds) {
            return null;
        }
        $h = intdiv($this->duration_seconds, 3600);
        $m = intdiv($this->duration_seconds % 3600, 60);
        $s = $this->duration_seconds % 60;

        return $h > 0 ? sprintf('%d:%02d:%02d', $h, $m, $s) : sprintf('%d:%02d', $m, $s);
    }
}
