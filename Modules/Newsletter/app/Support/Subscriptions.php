<?php

namespace Modules\Newsletter\Support;

use Illuminate\Support\Facades\Notification;
use Modules\Newsletter\Models\MailingList;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Notifications\ConfirmNewsletter;

/**
 * Subscribing (FR-NLT-01/02/08): the same email is one subscriber (BRL-NLT-01); a new or pending one
 * gets the confirmation email; one who left and signs up again on the form starts over as pending.
 * "trusted" (Admission's confirmed email) is subscribed at once.
 */
class Subscriptions
{
    /** @param list<int> $listIds */
    public function subscribe(string $email, array $listIds, string $source, ?string $name = null, bool $trusted = false): Subscriber
    {
        $subscriber = Subscriber::query()->firstOrNew(['email' => Subscriber::normalize($email)]);
        $subscriber->name = $subscriber->name ?: ($name !== null ? mb_substr(trim($name), 0, 191) : null);
        $subscriber->source ??= $source;
        if (! $subscriber->exists || $subscriber->status !== 'subscribed') {
            $subscriber->status = $trusted ? 'subscribed' : 'pending';
            $subscriber->verified_at = $trusted ? ($subscriber->verified_at ?? now()) : $subscriber->verified_at;
            $subscriber->unsubscribed_at = null;
        }
        $subscriber->is_active = true;
        $subscriber->save();
        $subscriber->join($listIds === [] ? [$this->general()->id] : $listIds);

        if ($subscriber->status === 'pending') {
            Notification::route('mail', $subscriber->email)->notify(new ConfirmNewsletter($subscriber));
        }

        return $subscriber;
    }

    public function general(): MailingList
    {
        return MailingList::ensure(MailingList::GENERAL, ['en' => 'General', 'bn' => 'সাধারণ']);
    }

    public function admissionList(): MailingList
    {
        return MailingList::ensure(MailingList::ADMISSION, ['en' => 'Admission Updates', 'bn' => 'ভর্তি আপডেট']);
    }

    /** FR-NLT-08: Admission's confirmed email subscriber → "Admission Updates"; leaving there leaves that list. */
    public function syncAdmission(object $admissionSubscriber): void
    {
        if (($admissionSubscriber->channel ?? null) !== 'email') {
            return;
        }
        $list = $this->admissionList();
        if ($admissionSubscriber->unsubscribed_at !== null || ! $admissionSubscriber->is_active) {
            $subscriber = Subscriber::query()->where('email', Subscriber::normalize((string) $admissionSubscriber->contact))->first();
            $subscriber?->lists()->updateExistingPivot($list->id, ['unsubscribed_at' => now()]);

            return;
        }
        if ($admissionSubscriber->verified_at !== null) {
            $existing = Subscriber::query()->where('email', Subscriber::normalize((string) $admissionSubscriber->contact))->first();
            if ($existing?->status === 'unsubscribed') {   // left the newsletter: suppression wins (BRL-NLT-02)
                return;
            }
            $this->subscribe((string) $admissionSubscriber->contact, [$list->id], 'admission', trusted: true);
        }
    }
}
