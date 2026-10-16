<?php

namespace Modules\Slider\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\ValidationException;
use Modules\Slider\Database\Factories\SlideFactory;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasActiveAt;
use Platform\Concerns\HasExpiry;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\IsAttachable;
use Platform\Contracts\Scopeable;
use Platform\Media\MediaItem;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * A hero slide or page banner (M-23, DB-05 §8 v1.7, SRS Content FR-SLD-01..07). A place's hero
 * is the slides shown there: one = a still hero, several = a slider; a slide with a video is a
 * full-screen background video with its picture as poster. One slide can show in many places
 * ("Show in" + main site). Pictures and video stay in the Media Library.
 */
class Slide extends BaseModel implements Scopeable
{
    use AppliesUserScope;
    use HasActiveAt;        // from
    use HasExpiry;          // until (FR-SLD-04)

    /** @use HasFactory<SlideFactory> */
    use HasFactory;

    use HasMediaUsage;      // image_desktop, image_mobile, video
    use HasSortOrder;
    use HasTranslations;
    use IsAttachable;

    public const PLACEMENTS = ['hero', 'page_banner'];

    public const POSITIONS = ['left', 'center', 'right'];

    public const MAX_BUTTONS = 2;

    public const MAX_BULLETS = 4;

    public static string $titleAttribute = 'title';

    protected $table = 'slider_slides';

    protected $fillable = ['placement', 'title', 'subtitle', 'buttons', 'bullets', 'text_position', 'overlay_opacity', 'video_on_mobile', 'sort_order', 'active_at', 'expires_at', 'is_active'];

    public array $translatable = ['title', 'subtitle', 'bullets'];

    protected $attributes = ['placement' => 'hero', 'text_position' => 'left', 'overlay_opacity' => 40, 'video_on_mobile' => false];

    protected function casts(): array
    {
        return ['buttons' => 'array', 'overlay_opacity' => 'integer', 'video_on_mobile' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Slide $slide): void {
            $errors = [];
            if (! in_array($slide->placement, self::PLACEMENTS, true)) {
                $errors['placement'] = __('slider::slider.errors.placement');
            }
            if (! in_array($slide->text_position, self::POSITIONS, true)) {
                $errors['text_position'] = __('slider::slider.errors.position');
            }
            if (count((array) $slide->buttons) > self::MAX_BUTTONS) {
                $errors['buttons'] = __('slider::slider.errors.buttons');
            }
            foreach ((array) $slide->getTranslations('bullets') as $list) {
                if (count((array) $list) > self::MAX_BULLETS) {
                    $errors['bullets'] = __('slider::slider.errors.bullets');
                }
            }
            if ($slide->overlay_opacity < 0 || $slide->overlay_opacity > 80) {
                $errors['overlay_opacity'] = __('slider::slider.errors.overlay');
            }
            if ($slide->expires_at !== null && $slide->active_at !== null && $slide->expires_at->lte($slide->active_at)) {
                $errors['expires_at'] = __('slider::slider.errors.expiry');
            }
            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }
        });
    }

    /** @return list<array{label: string, url: string}> in the current language */
    public function buttonList(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        return collect((array) $this->buttons)->map(fn (array $b): ?array => filled($b['url'] ?? null) && filled($label = ($b['label'][$locale] ?? null) ?: collect((array) ($b['label'] ?? []))->filter()->first())
            ? ['label' => (string) $label, 'url' => (string) $b['url']] : null)->filter()->values()->all();
    }

    /** @return list<string> */
    public function bulletList(?string $locale = null): array
    {
        return array_values(array_filter((array) $this->getTranslation('bullets', $locale ?? app()->getLocale(), true), 'filled'));
    }

    public function desktopImage(): ?MediaItem
    {
        return $this->firstMedia('image_desktop');
    }

    public function mobileImage(): ?MediaItem
    {
        return $this->firstMedia('image_mobile') ?? $this->desktopImage();
    }

    /** The background video, only a Media Library video. */
    public function videoItem(): ?MediaItem
    {
        $video = $this->firstMedia('video');

        return $video?->kind === 'video' ? $video : null;
    }

    protected static function newFactory(): SlideFactory
    {
        return SlideFactory::new();
    }
}
