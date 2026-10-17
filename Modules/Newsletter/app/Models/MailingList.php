<?php

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Newsletter\Database\Factories\MailingListFactory;
use Platform\Concerns\HasSortOrder;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/** A mailing list (M-39, DB-06 v1.3 §10, FR-NLT-04/08): general, admission-updates, notices …; a public one is offered on the form. */
class MailingList extends BaseModel
{
    /** @use HasFactory<MailingListFactory> */
    use HasFactory;

    use HasSortOrder;
    use HasTranslations;

    public const GENERAL = 'general';

    public const ADMISSION = 'admission-updates';

    public static string $titleAttribute = 'name';

    protected $table = 'newsletter_lists';

    protected $fillable = ['key', 'name', 'description', 'is_public', 'sort_order', 'is_active'];

    protected $attributes = ['is_public' => true];

    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (MailingList $list): void {
            $list->key = Str::slug((string) ($list->key ?: $list->getTranslation('name', config('app.fallback_locale', 'en'), true)));
            if ($list->key === '' || self::query()->whereKeyNot($list->getKey())->where('key', $list->key)->exists()) {
                throw ValidationException::withMessages(['key' => __('newsletter::newsletter.errors.list_key')]);
            }
        });
    }

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(Subscriber::class, 'newsletter_list_subscriber', 'list_id', 'subscriber_id')->withPivot(['subscribed_at', 'unsubscribed_at']);
    }

    /** The list with this key, made when missing (general, admission-updates). */
    public static function ensure(string $key, array $name, bool $public = true): self
    {
        return self::query()->where('key', $key)->first() ?? self::query()->create(['key' => $key, 'name' => $name, 'is_public' => $public, 'is_active' => true]);
    }

    protected static function newFactory(): MailingListFactory
    {
        return MailingListFactory::new();
    }
}
