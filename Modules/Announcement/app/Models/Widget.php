<?php

namespace Modules\Announcement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\ValidationException;
use Modules\Announcement\Database\Factories\WidgetFactory;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasActiveAt;
use Platform\Concerns\HasExpiry;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\IsAttachable;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * An announcement widget (M-30, DB-05 §15 v1.11, SRS Content FR-ANW-01..07): a greeting popup,
 * a ticker line, a countdown or an announcement bar — shown in its places ("Show in", main
 * site) on the homepage, on every page or on chosen module pages, from / until.
 */
class Widget extends BaseModel implements Scopeable
{
    use AppliesUserScope;
    use HasActiveAt;        // from
    use HasExpiry;          // until (FR-ANW-03)

    /** @use HasFactory<WidgetFactory> */
    use HasFactory;

    use HasMediaUsage;      // image (popup)
    use HasSortOrder;
    use HasTranslations;
    use IsAttachable;

    public const TYPES = ['popup', 'ticker', 'countdown', 'bar'];

    public const FREQUENCIES = ['each_visit', 'daily', 'once'];

    public const PAGES = ['home', 'all', 'selected'];

    public const COLORS = ['color-primary', 'color-secondary', 'color-accent', 'color-danger'];

    public static string $titleAttribute = 'title';

    protected $table = 'announcement_widgets';

    protected $fillable = [
        'widget_type', 'title', 'body', 'link_url', 'button_text', 'target_at', 'after_end', 'after_end_text', 'frequency',
        'is_dismissible', 'color_token', 'priority', 'pages', 'route_keys', 'include_latest_notices', 'sort_order',
        'active_at', 'expires_at', 'is_active',
    ];

    public array $translatable = ['title', 'body', 'button_text', 'after_end_text'];

    protected $attributes = ['after_end' => 'hide', 'frequency' => 'daily', 'is_dismissible' => true, 'priority' => 0, 'pages' => 'home', 'include_latest_notices' => false];

    protected function casts(): array
    {
        return [
            'target_at' => 'datetime', 'is_dismissible' => 'boolean', 'priority' => 'integer', 'route_keys' => 'array', 'include_latest_notices' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Widget $widget): void {
            $errors = [];
            foreach (['widget_type' => self::TYPES, 'frequency' => self::FREQUENCIES, 'pages' => self::PAGES, 'after_end' => ['hide', 'message']] as $field => $allowed) {
                if (! in_array($widget->{$field}, $allowed, true)) {
                    $errors[$field] = __('announcement::announcement.errors.choice');
                }
            }
            if ($widget->color_token !== null && ! in_array($widget->color_token, self::COLORS, true)) {
                $errors['color_token'] = __('announcement::announcement.errors.choice');
            }
            if ($widget->widget_type === 'countdown' && $widget->target_at === null) {
                $errors['target_at'] = __('announcement::announcement.errors.target');
            }
            if ($widget->pages === 'selected' && array_filter((array) $widget->route_keys) === []) {
                $errors['route_keys'] = __('announcement::announcement.errors.pages');
            }
            if ($widget->link_url !== null && $widget->link_url !== '' && ! preg_match('#^(https?://\S+|/\S*)$#i', (string) $widget->link_url)) {
                $errors['link_url'] = __('announcement::announcement.errors.url');
            }
            if ($widget->expires_at !== null && $widget->active_at !== null && $widget->expires_at->lte($widget->active_at)) {
                $errors['expires_at'] = __('announcement::announcement.errors.expiry');
            }
            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }
        });
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where($this->qualifyColumn('widget_type'), $type);
    }

    /**
     * Shown on this page: the site's homepage, any page, or a chosen page — a module page key
     * ("notice.index") or an address of the site ("/admission", and the pages below it).
     *
     * @param  string  $path  the address inside the site, without language or microsite ("admission/fees")
     */
    public function showsOn(?string $page, string $path = ''): bool
    {
        return match ($this->pages) {
            'all' => true,
            'home' => $page === 'home',
            default => collect((array) $this->route_keys)->contains(fn (string $chosen): bool => str_starts_with($chosen, '/')
                ? (($p = trim($chosen, '/')) === '' ? $page === 'home' : (trim($path, '/') === $p || str_starts_with(trim($path, '/'), $p.'/')))
                : $page === $chosen),
        };
    }

    public function countdownOver(): bool
    {
        return $this->target_at !== null && $this->target_at->isPast();
    }

    public function href(): ?string
    {
        if (blank($this->link_url)) {
            return null;
        }

        return str_starts_with((string) $this->link_url, '/') ? url($this->link_url) : (string) $this->link_url;
    }

    /** Remembering a dismissed / seen widget until it changes. */
    public function memoryKey(): string
    {
        return 'uwms-anw-'.$this->id.'-'.$this->updated_at?->timestamp;
    }

    protected static function newFactory(): WidgetFactory
    {
        return WidgetFactory::new();
    }
}
