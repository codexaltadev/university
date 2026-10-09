<?php

/*
| Phase 2.12 — Google Drive as a backup destination (FR-BKP-04): connect the
| Google account (OAuth with state), back up to a folder, list, download, delete.
| Google is faked with an in-memory Drive.
*/

use Filament\Facades\Filament;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\Backup\Http\GoogleDriveController;
use Modules\Backup\Support\BackupDestinations;
use Modules\Backup\Support\BackupRunner;
use Platform\Settings\Setting;

/** A tiny Google Drive: folders and files in memory, answering the REST calls the adapter makes. */
function fakeGoogleDrive(): ArrayObject
{
    $drive = new ArrayObject(['items' => [], 'sessions' => [], 'next' => 1]);

    Http::fake(function (Request $request) use ($drive) {
        $url = $request->url();

        if (str_starts_with($url, 'https://oauth2.googleapis.com/token')) {
            return Http::response(['access_token' => 'ya29.test', 'expires_in' => 3600, 'refresh_token' => 'refresh-from-google']);
        }

        if (str_starts_with($url, 'https://www.googleapis.com/oauth2/v3/userinfo')) {
            return Http::response(['email' => 'it@university.edu']);
        }

        if (str_starts_with($url, 'https://www.googleapis.com/upload/drive/v3/files')) {
            $id = 'session-'.$drive['next']++;
            $sessions = $drive['sessions'];
            $sessions[$id] = $request->data();
            $drive['sessions'] = $sessions;

            return Http::response('', 200, ['Location' => "https://upload.test/{$id}"]);
        }

        if (str_starts_with($url, 'https://upload.test/')) {
            $meta = $drive['sessions'][basename(parse_url($url, PHP_URL_PATH))];
            $id = 'file-'.$drive['next']++;
            $items = $drive['items'];
            $items[$id] = ['id' => $id, 'name' => $meta['name'], 'parent' => $meta['parents'][0], 'mimeType' => 'application/zip',
                'size' => (string) strlen($request->body()), 'modifiedTime' => now()->toIso8601String(), 'content' => $request->body()];
            $drive['items'] = $items;

            return Http::response(['id' => $id]);
        }

        $path = parse_url($url, PHP_URL_PATH);

        if ($request->method() === 'GET' && $path === '/drive/v3/files') {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            preg_match("/name = '([^']+)'/", $query['q'], $name);
            preg_match("/'([^']+)' in parents/", $query['q'], $parent);
            $folderOnly = str_contains($query['q'], "mimeType = 'application/vnd.google-apps.folder'");
            $filesOnly = str_contains($query['q'], "mimeType != 'application/vnd.google-apps.folder'");

            $found = collect($drive['items'])
                ->filter(fn (array $i): bool => $i['parent'] === $parent[1] && (! $name || $i['name'] === $name[1]))
                ->filter(fn (array $i): bool => ! $folderOnly || $i['mimeType'] === 'application/vnd.google-apps.folder')
                ->filter(fn (array $i): bool => ! $filesOnly || $i['mimeType'] !== 'application/vnd.google-apps.folder')
                ->map(fn (array $i): array => collect($i)->except(['content', 'parent'])->all())->values()->all();

            return Http::response(['files' => $found]);
        }

        if ($request->method() === 'POST' && $path === '/drive/v3/files') {
            $id = 'folder-'.$drive['next']++;
            $items = $drive['items'];
            $items[$id] = ['id' => $id, 'name' => $request['name'], 'parent' => $request['parents'][0], 'mimeType' => $request['mimeType']];
            $drive['items'] = $items;

            return Http::response(['id' => $id]);
        }

        if (preg_match('#^/drive/v3/files/([^/]+)$#', $path, $m)) {
            if ($request->method() === 'DELETE') {
                $items = $drive['items'];
                unset($items[$m[1]]);
                $drive['items'] = $items;

                return Http::response('', 204);
            }

            return isset($drive['items'][$m[1]]) ? Http::response($drive['items'][$m[1]]['content']) : Http::response(['error' => ['message' => 'File not found']], 404);
        }

        return Http::response(['error' => ['message' => "Unexpected {$request->method()} {$url}"]], 500);
    });

    return $drive;
}

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Storage::fake('local');
});

it('connects the Google account through Google\'s consent page, checking the state (FR-BKP-04)', function () {
    fakeGoogleDrive();
    $this->actingAs(superAdmin());

    // Client details first
    $this->get(route('backup.gdrive.connect'))->assertRedirect(url('/admin/settings').'?tab=tab-backup');

    settings()->save('backup', ['destination' => 'gdrive', 'gdrive_client_id' => 'client-123.apps.googleusercontent.com', 'gdrive_client_secret' => 'gocspx-secret'], encrypted: BackupDestinations::SECRETS);

    $redirect = $this->get(route('backup.gdrive.connect'))->assertRedirect()->headers->get('Location');
    parse_str((string) parse_url($redirect, PHP_URL_QUERY), $query);
    expect($redirect)->toStartWith('https://accounts.google.com/o/oauth2/v2/auth')
        ->and($query)->toMatchArray(['client_id' => 'client-123.apps.googleusercontent.com', 'access_type' => 'offline', 'redirect_uri' => route('backup.gdrive.callback')])
        ->and($query['scope'])->toContain('auth/drive.file');

    // A forged callback is refused
    $this->get(route('backup.gdrive.callback', ['state' => 'wrong', 'code' => 'x']))->assertForbidden();

    $this->withSession([GoogleDriveController::STATE => 'good-state'])
        ->get(route('backup.gdrive.callback', ['state' => 'good-state', 'code' => 'auth-code']))
        ->assertRedirect(url('/admin/settings').'?tab=tab-backup');

    expect(settings()->group('backup')['gdrive_refresh_token'])->toBe('refresh-from-google')
        ->and(settings('backup.gdrive_account'))->toBe('it@university.edu')
        ->and(Setting::query()->where('key', 'gdrive_refresh_token')->value('value'))->not->toContain('refresh-from-google');

    Http::assertSent(fn (Request $r): bool => str_contains($r->url(), 'oauth2.googleapis.com/token') && $r['code'] === 'auth-code' && $r['client_secret'] === 'gocspx-secret');
});

it('only lets Super Admins connect', function () {
    $this->actingAs(makeUser());
    $this->get(route('backup.gdrive.connect'))->assertForbidden();
});

it('backs up to a Google Drive folder, finds, downloads and removes it there', function () {
    $this->drive = fakeGoogleDrive();
    settings()->save('backup', [
        'destination' => 'gdrive', 'gdrive_client_id' => 'id', 'gdrive_client_secret' => 'secret',
        'gdrive_refresh_token' => 'refresh', 'gdrive_folder' => 'DIU Backups',
    ], encrypted: BackupDestinations::SECRETS);

    $run = app(BackupRunner::class)->run('database');

    expect($run->status)->toBe('success')
        ->and($run->disk)->toBe(BackupDestinations::REMOTE_DISK)
        ->and($run->size_bytes)->toBeGreaterThan(0);

    $names = collect($this->drive['items'])->pluck('name')->all();
    expect($names)->toContain('DIU Backups', 'uwms-backups', basename($run->path));

    $disk = Storage::disk(BackupDestinations::REMOTE_DISK);
    expect($disk->exists($run->path))->toBeTrue()
        ->and($disk->size($run->path))->toBe($run->size_bytes)
        ->and($disk->allFiles('uwms-backups'))->toBe([$run->path])
        ->and(strlen($disk->get($run->path)))->toBe($run->size_bytes);

    $disk->delete($run->path);
    expect($disk->exists($run->path))->toBeFalse();
});

it('reports a broken Google connection when testing it', function () {
    Http::fake(['oauth2.googleapis.com/*' => Http::response(['error' => 'invalid_grant', 'error_description' => 'Token has been expired or revoked.'], 400)]);

    $error = BackupDestinations::test(['destination' => 'gdrive', 'gdrive_client_id' => 'id', 'gdrive_client_secret' => 's', 'gdrive_refresh_token' => 'old']);

    expect($error)->toContain('Token has been expired or revoked');
});
