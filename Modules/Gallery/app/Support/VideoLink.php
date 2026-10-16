<?php

namespace Modules\Gallery\Support;

/** YouTube / Vimeo / Facebook links: provider and id, embed address, thumbnail (FR-GAL-02). */
final class VideoLink
{
    public const PROVIDERS = ['youtube', 'vimeo', 'facebook'];

    /** @return array{provider: string, id: ?string}|null */
    public static function parse(string $url): ?array
    {
        $url = trim($url);

        return match (true) {
            (bool) preg_match('~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([\w-]{11})~', $url, $m) => ['provider' => 'youtube', 'id' => $m[1]],
            (bool) preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m) => ['provider' => 'vimeo', 'id' => $m[1]],
            (bool) preg_match('~^https?://(?:www\.|m\.|web\.)?(?:facebook\.com|fb\.watch)/~', $url) => ['provider' => 'facebook', 'id' => preg_match('~/videos/(?:[^/]+/)?(\d+)~', $url, $m) ? $m[1] : null],
            default => null,
        };
    }

    public static function embed(?string $provider, ?string $id, string $url): ?string
    {
        return match ($provider) {
            'youtube' => $id ? 'https://www.youtube-nocookie.com/embed/'.$id : null,
            'vimeo' => $id ? 'https://player.vimeo.com/video/'.$id : null,
            'facebook' => 'https://www.facebook.com/plugins/video.php?href='.rawurlencode($url).'&show_text=false',
            default => null,
        };
    }

    public static function thumbnail(?string $provider, ?string $id): ?string
    {
        return $provider === 'youtube' && $id ? 'https://i.ytimg.com/vi/'.$id.'/hqdefault.jpg' : null;
    }
}
