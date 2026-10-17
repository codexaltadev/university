<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Facades\Schema;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Support\Subscriptions;

/**
 * S18N Newsletter (DB-06 v1.3 §10): `newsletters` (email, subscription_status) → subscribers of the
 * "General" list, source "legacy" — status 1 subscribed (they signed up on the old site), 0
 * unsubscribed (kept as a suppression, BRL-NLT-02). The same email twice is one subscriber; an
 * address that is not an email is left out.
 */
class S18Newsletter extends Step
{
    public static function key(): string
    {
        return 'S18N';
    }

    public static function title(): string
    {
        return 'Newsletter';
    }

    public static function requires(): array
    {
        return ['newsletter'];
    }

    protected function handle(): void
    {
        if (! Schema::connection('legacy')->hasTable('newsletters')) {
            $this->report->review('No newsletters table in the legacy database: nothing to migrate.');

            return;
        }
        $general = app(Subscriptions::class)->general();
        $seen = [];
        foreach ($this->rows('newsletters') as $row) {
            $email = Subscriber::normalize((string) $row->email);
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->report->skipped('newsletters', 'not an email');

                continue;
            }
            if (isset($seen[$email])) {
                $this->report->skipped('newsletters', 'same email again');

                continue;
            }
            $seen[$email] = true;
            $subscribed = (int) $row->subscription_status === 1;
            $this->upsert('newsletters', $row, Subscriber::class, [
                'email' => $email,
                'status' => $subscribed ? 'subscribed' : 'unsubscribed',
                'verified_at' => $row->created_at,
                'source' => 'legacy',
                'unsubscribed_at' => $subscribed ? null : $row->updated_at,
                'is_active' => true,
                'created_at' => $row->created_at,
            ], after: fn (Subscriber $s, bool $created) => $created ? $s->join([$general->id]) : null,
                existing: $this->same(Subscriber::class, ['email' => $email]));
        }
    }
}
