<?php

namespace Modules\Testimonials\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\ValidationException;
use Modules\Testimonials\Database\Factories\TestimonialFactory;
use Platform\Assignments\Assignment;
use Platform\Assignments\PersonView;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\IsAssignable;
use Platform\Concerns\IsAttachable;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * What a student, alumnus, parent, employer or partner says (M-21, DB-05 §6 v1.5, SRS Content
 * FR-TEST-01..04). The person is one assignment (role "author") — from a directory, so name,
 * photo and designation come from there, or a manual person; a photo of its own is the fallback.
 * Where it shows is "Show in" and the main site; order is dragged.
 */
class Testimonial extends BaseModel implements Scopeable
{
    use AppliesUserScope;

    /** @use HasFactory<TestimonialFactory> */
    use HasFactory;

    use HasMediaUsage;      // photo (fallback when the person has none)
    use HasSortOrder;
    use HasTranslations;
    use IsAssignable;       // the author
    use IsAttachable;

    public const TYPES = ['student', 'alumni', 'parent', 'faculty', 'employer', 'partner', 'visitor'];

    public const ROLE = 'author';

    public const PATH = 'testimonials';

    public static string $titleAttribute = 'quote';

    protected $table = 'testimonials_testimonials';

    protected $fillable = ['quote', 'testimonial_type', 'country', 'rating', 'video_url', 'source_key', 'sort_order', 'is_active'];

    public array $translatable = ['quote'];

    protected function casts(): array
    {
        return ['rating' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (Testimonial $testimonial): void {
            if (! in_array($testimonial->testimonial_type, self::TYPES, true)) {
                throw ValidationException::withMessages(['testimonial_type' => __('testimonials::testimonials.errors.type')]);
            }
            if ($testimonial->rating !== null && ($testimonial->rating < 1 || $testimonial->rating > 5)) {
                throw ValidationException::withMessages(['rating' => __('testimonials::testimonials.errors.rating')]);
            }
        });
    }

    public function author(): ?Assignment
    {
        return $this->assignments()->where('role_key', self::ROLE)->orderBy('sort_order')->with('person')->first();
    }

    /** The person as the website shows them (name, designation, photo; the assignment's own title wins). */
    public function person(): ?PersonView
    {
        return $this->people(self::ROLE)->first();
    }

    public function photoUrl(): ?string
    {
        return $this->person()?->photoUrl() ?? $this->firstMedia('photo')?->url('medium');
    }

    /** YouTube / Drive link as an embeddable address, or null. */
    public function videoEmbed(): ?string
    {
        $url = (string) $this->video_url;

        return match (true) {
            (bool) preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([\w-]{11})~', $url, $m) => 'https://www.youtube-nocookie.com/embed/'.$m[1],
            (bool) preg_match('~drive\.google\.com/file/d/([\w-]+)~', $url, $m) => 'https://drive.google.com/file/d/'.$m[1].'/preview',
            default => null,
        };
    }

    protected static function newFactory(): TestimonialFactory
    {
        return TestimonialFactory::new();
    }
}
