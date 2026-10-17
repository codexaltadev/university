<?php

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Newsletter\Database\Factories\SubscriberFactory;
use Platform\Models\BaseModel;

/**
 * A subscriber (M-39, DB-06 v1.3 §10, FR-NLT-01..03): pending until the email link is clicked;
 * an unsubscribed one stays as a suppression (BRL-NLT-02) — no campaign, an import cannot bring it back.
 */
class Subscriber extends BaseModel
{
    /** @use HasFactory<SubscriberFactory> */
    use HasFactory;

    public const STATUSES = ['pending', 'subscribed', 'unsubscribed', 'bounced'];

    public const SOURCES = ['footer', 'page', 'admission', 'import', 'legacy', 'admin'];

    public static string $titleAttribute = 'email';

    protected $table = 'newsletter_subscribers';

    protected $fillable = ['email', 'name', 'status', 'verified_at', 'source', 'unsubscribed_at', 'unsubscribe_reason', 'is_active'];

    protected $hidden = ['token'];

    protected $attributes = ['status' => 'pending', 'source' => 'admin'];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime', 'unsubscribed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (Subscriber $s): void {
            $s->email = self::normalize((string) $s->email);
            if (! filter_var($s->email, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages(['email' => __('newsletter::newsletter.errors.email')]);
            }
            if (! in_array($s->status, self::STATUSES, true)) {
                throw ValidationException::withMessages(['status' => __('newsletter::newsletter.errors.status')]);
            }
            if (self::query()->whereKeyNot($s->getKey())->where('email', $s->email)->exists()) {
                throw ValidationException::withMessages(['email' => __('newsletter::newsletter.errors.email_taken')]);
            }
            $s->token ??= Str::random(64);
            if ($s->status === 'unsubscribed') {
                $s->unsubscribed_at ??= now();
            }
        });
    }

    public static function normalize(string $email): string
    {
        return Str::lower(trim($email));
    }

    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(MailingList::class, 'newsletter_list_subscriber', 'subscriber_id', 'list_id')->withPivot(['subscribed_at', 'unsubscribed_at']);
    }

    /** May get campaigns: confirmed, not unsubscribed or bounced, active. */
    public function scopeReachable(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), 'subscribed')->where($this->qualifyColumn('is_active'), true);
    }

    /** Join lists (again); a list left before is joined again. */
    public function join(array $listIds): void
    {
        $now = now();
        $this->lists()->syncWithoutDetaching(collect($listIds)->mapWithKeys(fn ($id): array => [(int) $id => ['subscribed_at' => $now, 'unsubscribed_at' => null]])->all());
    }

    public function confirm(): void
    {
        if ($this->status === 'pending') {
            $this->forceFill(['status' => 'subscribed', 'verified_at' => $this->verified_at ?? now()])->save();
        }
    }

    public function unsubscribe(?string $reason = null): void
    {
        $this->forceFill(['status' => 'unsubscribed', 'unsubscribed_at' => now(), 'unsubscribe_reason' => $reason !== null ? mb_substr($reason, 0, 255) : $this->unsubscribe_reason])->save();
        $this->lists()->newPivotQuery()->update(['unsubscribed_at' => now()]);
    }

    protected static function newFactory(): SubscriberFactory
    {
        return SubscriberFactory::new();
    }
}
