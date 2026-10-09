<?php

namespace Modules\Settings\Support;

use Illuminate\Mail\MailManager;

/**
 * Settings › Mail over config/mail.php (FR-SET-07). With "use own SMTP" off the
 * server's .env settings stay; the sender name / address apply either way.
 */
class MailConfig
{
    public static function apply(): void
    {
        $mail = settings()->group('mail');

        if (filled($mail['from_address'] ?? null)) {
            config(['mail.from.address' => $mail['from_address']]);
        }

        if (filled($mail['from_name'] ?? null)) {
            config(['mail.from.name' => $mail['from_name']]);
        }

        if (! ($mail['enabled'] ?? false) || blank($mail['host'] ?? null)) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $mail['host'],
            'mail.mailers.smtp.port' => (int) ($mail['port'] ?? 587),
            'mail.mailers.smtp.username' => $mail['username'] ?? null,
            'mail.mailers.smtp.password' => $mail['password'] ?? null,
            'mail.mailers.smtp.scheme' => ($mail['encryption'] ?? 'tls') === 'ssl' ? 'smtps' : 'smtp',
        ]);

        // Mailers already built keep their old transport
        if (app()->resolved('mail.manager')) {
            app(MailManager::class)->purge('smtp');
        }
    }
}
