<?php

namespace Modules\Page\Blocks;

use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Platform\Pages\PageBlock;

final class EmbedBlock extends PageBlock
{
    public static function name(): string
    {
        return 'embed';
    }

    public static function label(): string
    {
        return __('page::page.blocks.embed');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedPlayCircle;
    }

    /** Player / map address for a saved link; null for anything else (no arbitrary iframes). */
    public static function embedUrl(string $url): ?string
    {
        return match (true) {
            (bool) preg_match('#^https://(?:www\.)?youtube\.com/watch\?(?:.*&)?v=([\w-]{6,20})#i', $url, $m),
            (bool) preg_match('#^https://youtu\.be/([\w-]{6,20})#i', $url, $m),
            (bool) preg_match('#^https://(?:www\.)?youtube\.com/(?:embed|shorts)/([\w-]{6,20})#i', $url, $m) => "https://www.youtube-nocookie.com/embed/{$m[1]}",
            (bool) preg_match('#^https://(?:player\.)?vimeo\.com/(?:video/)?(\d{5,12})#i', $url, $m) => "https://player.vimeo.com/video/{$m[1]}",
            (bool) preg_match('#^https://(?:www\.)?google\.com/maps/embed\?#i', $url),
            (bool) preg_match('#^https://maps\.google\.com/maps\?.*output=embed#i', $url) => $url,
            (bool) preg_match('#^https://(?:www\.)?facebook\.com/.+/videos/#i', $url) => 'https://www.facebook.com/plugins/video.php?href='.rawurlencode($url),
            default => null,
        };
    }

    /** YouTube, Vimeo, Google Maps, Facebook video: the website turns the link into the player (embedUrl). */
    public static function schema(): array
    {
        return [
            TextInput::make('url')->label(__('page::page.fields.url'))->url()->required()->maxLength(1000)
                ->regex('#^https://(www\.)?(youtube\.com|youtu\.be|vimeo\.com|player\.vimeo\.com|google\.com/maps|maps\.google\.com|facebook\.com)/#i')
                ->helperText(__('page::page.help.embed')),
            TextInput::make('caption')->label(__('page::page.fields.caption'))->maxLength(255),
        ];
    }
}
