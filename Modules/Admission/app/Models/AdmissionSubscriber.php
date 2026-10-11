<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Builder;
use Platform\Models\BaseModel;

/**
 * Someone who asked for admission news by email or WhatsApp (FR-ADM-09), on a topic
 * ("admission", "Silver Jubilee" …). Unsubscribed ones are kept with the reason.
 */
class AdmissionSubscriber extends BaseModel
{
    public const CHANNELS = ['email', 'whatsapp'];

    public static string $titleAttribute = 'contact';

    protected $table = 'admission_subscribers';

    protected $fillable = ['channel', 'contact', 'topic', 'source', 'verified_at', 'unsubscribed_at', 'unsubscribe_reason', 'is_active'];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime', 'unsubscribed_at' => 'datetime'];
    }

    public function scopeSubscribed(Builder $query): Builder
    {
        return $query->whereNull('unsubscribed_at')->where('is_active', true);
    }

    /** A phone number as digits with a leading "+" (WhatsApp), an email in lower case. */
    public static function normalizeContact(string $channel, string $contact): string
    {
        $contact = trim($contact);

        return $channel === 'whatsapp'
            ? (str_starts_with($contact, '+') ? '+' : '').preg_replace('/\D+/', '', $contact)
            : mb_strtolower($contact);
    }
}
