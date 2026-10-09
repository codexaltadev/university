<?php

namespace Platform\Theme;

/**
 * WCAG 2 colour contrast (FR-THEME-04): the ratio of two colours and the text
 * colour that reads best on a background.
 */
final class Contrast
{
    /** Normal text needs 4.5:1 (AA). */
    public const AA = 4.5;

    public static function ratio(string $a, string $b): float
    {
        [$light, $dark] = [max(self::luminance($a), self::luminance($b)), min(self::luminance($a), self::luminance($b))];

        return round(($light + 0.05) / ($dark + 0.05), 2);
    }

    /** White or near-black, whichever is easier to read on $background. */
    public static function readableOn(string $background, string $dark = '#111827'): string
    {
        return self::ratio($background, '#FFFFFF') >= self::ratio($background, $dark) ? '#FFFFFF' : $dark;
    }

    public static function isHex(?string $color): bool
    {
        return is_string($color) && (bool) preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $color);
    }

    public static function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        $channels = array_map(function (string $pair): float {
            $c = hexdec($pair) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split(substr(str_pad($hex, 6, '0'), 0, 6), 2));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }
}
