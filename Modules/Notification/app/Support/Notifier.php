<?php

namespace Modules\Notification\Support;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Modules\Notification\Models\Preference;
use Modules\Notification\Models\Template;
use Modules\Notification\Notifications\PlatformNotification;
use Platform\Localization\Languages;
use Platform\Notifications\Notifications;
use Platform\Notifications\NotificationType;

/**
 * The sender behind Platform\Notifications (C-18): recipients of the type
 * (FR-NOTI-07), each user's channels (FR-NOTI-05), the admin's template in the
 * user's language with placeholders filled (FR-NOTI-06), queued (FR-NOTI-02).
 */
class Notifier
{
    public const CHANNELS = ['database', 'mail'];

    public function send(NotificationType $notification): int
    {
        $sent = 0;

        foreach ($notification->recipients()->unique('id') as $user) {
            $channels = $this->channelsFor($user, $notification::key(), $notification::defaultChannels());

            if ($channels === []) {
                continue;
            }

            [$subject, $body] = $this->render($notification, $this->localeOf($user));
            $user->notify(new PlatformNotification($notification::key(), $subject, $body, $notification->url(), $channels));
            $sent++;
        }

        return $sent;
    }

    /** @return array{0: string, 1: string} subject and body in a language */
    public function render(NotificationType $notification, string $locale): array
    {
        $template = Template::query()->where('key', $notification::key())->where('is_active', true)->first();
        $default = app(Languages::class)->default();

        $pick = fn (string $field, string $fallback): string => (string) ($template?->getTranslation($field, $locale, false)
            ?: $template?->getTranslation($field, $default, false)
            ?: $fallback);

        $subject = $pick('subject', $notification::defaultSubject($locale));
        $body = $pick('body', $notification::defaultBody($locale));
        $values = collect($notification->values($locale))->mapWithKeys(fn (string $v, string $k): array => ['{'.$k.'}' => $v])->all();

        return [strtr($subject, $values), strtr($body, $values)];
    }

    /**
     * @param  list<string>  $defaults
     * @return list<string>
     */
    public function channelsFor(User $user, string $type, array $defaults): array
    {
        $stored = Preference::query()->where('user_id', $user->id)->where('type', $type)->value('channels');

        return array_values(array_intersect(self::CHANNELS, is_array($stored) ? $stored : $defaults));
    }

    /**
     * Save a user's choice; the type's default removes the row (FR-NOTI-05).
     *
     * @param  list<string>  $channels
     */
    public function setChannels(User $user, string $type, array $channels): void
    {
        $class = app(Notifications::class)->types()[$type] ?? null;
        $channels = array_values(array_intersect(self::CHANNELS, $channels));

        if ($class && $channels === array_values(array_intersect(self::CHANNELS, $class::defaultChannels()))) {
            Preference::query()->where('user_id', $user->id)->where('type', $type)->delete();

            return;
        }

        Preference::query()->updateOrCreate(['user_id' => $user->id, 'type' => $type], ['channels' => $channels]);
    }

    /** Read in-app notifications older than 90 days (FR-NOTI-08). */
    public static function clean(): int
    {
        return DatabaseNotification::query()
            ->whereNotNull('read_at')->where('read_at', '<', now()->subDays(90))->delete();
    }

    protected function localeOf(User $user): string
    {
        $languages = app(Languages::class);

        return $user->locale && in_array($user->locale, $languages->codes(), true) ? $user->locale : $languages->default();
    }
}
