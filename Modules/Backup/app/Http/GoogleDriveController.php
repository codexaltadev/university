<?php

namespace Modules\Backup\Http;

use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Backup\Settings\BackupSettings;
use Modules\Backup\Support\GoogleDrive\GoogleDriveClient;
use Throwable;

/**
 * "Connect Google account" for backups (FR-BKP-04): Google's consent page, then
 * the refresh token is saved (encrypted) in Settings › Backup. Super Admins only.
 */
class GoogleDriveController
{
    public const STATE = 'backup.gdrive_state';

    public function connect(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $clientId = settings('backup.gdrive_client_id');

        if (blank($clientId) || blank(settings()->group('backup')['gdrive_client_secret'] ?? null)) {
            return $this->back(__('backup::backup.gdrive.need_client'), false);
        }

        $state = Str::random(40);
        $request->session()->put(self::STATE, $state);

        return redirect()->away(GoogleDriveClient::authorizationUrl($clientId, route('backup.gdrive.callback'), $state));
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $expected = $request->session()->pull(self::STATE);
        abort_unless($expected && hash_equals($expected, (string) $request->query('state')), 403);   // CSRF on the OAuth round trip

        if ($request->filled('error') || ! $request->filled('code')) {
            return $this->back(__('backup::backup.gdrive.denied'), false);
        }

        try {
            $values = settings()->group('backup');
            $token = GoogleDriveClient::exchangeCode($values['gdrive_client_id'], $values['gdrive_client_secret'], (string) $request->query('code'), route('backup.gdrive.callback'));
        } catch (Throwable $e) {
            return $this->back(__('backup::backup.gdrive.failed').' '.$e->getMessage(), false);
        }

        settings()->saveFor(settings()->definition('backup') ?? app(BackupSettings::class), [
            'gdrive_refresh_token' => $token['refresh_token'],
            'gdrive_account' => $token['email'],
        ]);
        activity('backup')->event('connected')->withProperties(['attributes' => ['google_account' => $token['email']]])->log('Google Drive connected');   // BRL-BKP-02

        return $this->back(__('backup::backup.gdrive.connected', ['account' => $token['email']]), true);
    }

    protected function back(string $message, bool $ok): RedirectResponse
    {
        Notification::make()->title($message)->{$ok ? 'success' : 'danger'}()->send();

        return redirect()->to(url('/admin/settings').'?tab=tab-backup');
    }
}
