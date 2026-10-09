<?php

namespace Modules\Backup\Support\GoogleDrive;

use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Google Drive REST API v3 through Laravel's HTTP client — no Google SDK. OAuth
 * with the university's own client ID / secret and the refresh token saved by
 * "Connect Google account"; scope drive.file, so only files this app created are
 * visible to it.
 */
class GoogleDriveClient
{
    public const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    public const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public const API = 'https://www.googleapis.com/drive/v3';

    public const UPLOAD = 'https://www.googleapis.com/upload/drive/v3/files';

    public const SCOPES = 'https://www.googleapis.com/auth/drive.file openid email';

    public const FOLDER = 'application/vnd.google-apps.folder';

    public function __construct(
        protected string $clientId,
        protected string $clientSecret,
        protected string $refreshToken,
    ) {}

    /** Google's consent page (FR-BKP-04, "Connect Google account"). */
    public static function authorizationUrl(string $clientId, string $redirectUri, string $state): string
    {
        return self::AUTH_URL.'?'.http_build_query([
            'client_id' => $clientId, 'redirect_uri' => $redirectUri, 'response_type' => 'code',
            'scope' => self::SCOPES, 'access_type' => 'offline', 'prompt' => 'consent', 'state' => $state,
        ]);
    }

    /**
     * The code from Google's redirect → refresh token and the account's email.
     *
     * @return array{refresh_token: string, email: ?string}
     */
    public static function exchangeCode(string $clientId, string $clientSecret, string $code, string $redirectUri): array
    {
        $token = Http::asForm()->post(self::TOKEN_URL, [
            'code' => $code, 'client_id' => $clientId, 'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri, 'grant_type' => 'authorization_code',
        ])->throw()->json();

        if (blank($token['refresh_token'] ?? null)) {
            throw new RuntimeException('Google did not return a refresh token.');
        }

        $email = Http::withToken($token['access_token'])->get('https://www.googleapis.com/oauth2/v3/userinfo')->json('email');

        return ['refresh_token' => $token['refresh_token'], 'email' => $email];
    }

    public function accessToken(): string
    {
        return Cache::remember('backup:gdrive-token:'.md5($this->clientId.$this->refreshToken), now()->addMinutes(50), function (): string {
            $response = Http::asForm()->post(self::TOKEN_URL, [
                'client_id' => $this->clientId, 'client_secret' => $this->clientSecret,
                'refresh_token' => $this->refreshToken, 'grant_type' => 'refresh_token',
            ]);

            if ($response->failed() || blank($response->json('access_token'))) {
                throw new RuntimeException('Google Drive sign-in failed: '.($response->json('error_description') ?? $response->json('error') ?? $response->status()).'. Connect the Google account again.');
            }

            return $response->json('access_token');
        });
    }

    public function api(): PendingRequest
    {
        return Http::withToken($this->accessToken())->acceptJson()->timeout(120);
    }

    /** @return array<string, mixed>|null the first file / folder with this name in a folder */
    public function find(string $name, string $parentId, ?bool $folder = null): ?array
    {
        $q = sprintf("name = '%s' and '%s' in parents and trashed = false", addcslashes($name, "\\'"), $parentId);

        if ($folder !== null) {
            $q .= $folder ? " and mimeType = '".self::FOLDER."'" : " and mimeType != '".self::FOLDER."'";
        }

        return $this->check($this->api()->get(self::API.'/files', [
            'q' => $q, 'fields' => 'files(id,name,mimeType,size,modifiedTime)', 'pageSize' => 1, 'spaces' => 'drive',
        ]))->json('files.0');
    }

    /** @return list<array<string, mixed>> files and folders in a folder */
    public function children(string $parentId): array
    {
        $items = [];
        $page = null;

        do {
            $response = $this->check($this->api()->get(self::API.'/files', array_filter([
                'q' => sprintf("'%s' in parents and trashed = false", $parentId),
                'fields' => 'nextPageToken,files(id,name,mimeType,size,modifiedTime)', 'pageSize' => 1000, 'pageToken' => $page,
            ])));
            $items = [...$items, ...(array) $response->json('files', [])];
            $page = $response->json('nextPageToken');
        } while ($page);

        return $items;
    }

    public function createFolder(string $name, string $parentId): string
    {
        return $this->check($this->api()->post(self::API.'/files', [
            'name' => $name, 'mimeType' => self::FOLDER, 'parents' => [$parentId],
        ]))->json('id');
    }

    /** Resumable upload: one session, the whole stream in one PUT (large backups too). */
    public function upload(string $name, string $parentId, mixed $stream, string $mimeType = 'application/octet-stream'): string
    {
        $session = $this->check(Http::withToken($this->accessToken())->timeout(60)
            ->withHeaders(['X-Upload-Content-Type' => $mimeType])
            ->post(self::UPLOAD.'?uploadType=resumable', ['name' => $name, 'parents' => [$parentId]]))
            ->header('Location');

        return $this->check(Http::timeout(3600)
            ->withBody(Utils::streamFor($stream), $mimeType)
            ->put($session))->json('id');
    }

    /** @return resource */
    public function download(string $id)
    {
        $stream = fopen('php://temp', 'w+b');
        $response = $this->check($this->api()->timeout(3600)->withOptions(['sink' => $stream])->get(self::API."/files/{$id}", ['alt' => 'media']));

        if (fstat($stream)['size'] === 0 && $response->body() !== '') {
            fwrite($stream, $response->body());   // a client that ignored the sink (tests)
        }

        rewind($stream);

        return $stream;
    }

    public function delete(string $id): void
    {
        $response = $this->api()->delete(self::API."/files/{$id}");

        if ($response->status() !== 404) {
            $this->check($response);
        }
    }

    protected function check(Response $response): Response
    {
        if ($response->failed()) {
            throw new RuntimeException('Google Drive: '.($response->json('error.message') ?? $response->status()));
        }

        return $response;
    }
}
