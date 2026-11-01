<?php

namespace Modules\Page\Blocks;

use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Platform\Pages\PageBlock;
use Platform\Settings\Settings;

/**
 * Location (DIU's /location): a campus of Settings › Contact — its name, address, phones, emails and map link beside the
 * map. Nothing is typed into the block itself, so the address is kept in one place.
 */
final class LocationBlock extends PageBlock
{
    public static function name(): string
    {
        return 'location';
    }

    public static function label(): string
    {
        return __('page::page.blocks.location');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedMapPin;
    }

    public static function schema(): array
    {
        return [
            Select::make('campus')->label(__('page::page.fields.campus'))
                ->options(fn (): array => collect((array) settings('contact.campuses'))->values()
                    ->mapWithKeys(fn (array $c, int $i): array => [$i => (string) (app(Settings::class)->translate($c['name'] ?? null) ?: '#'.($i + 1))])->all())
                ->default(0)->selectablePlaceholder(false)
                ->helperText(__('page::page.help.location')),
        ];
    }

    /**
     * The campus the block shows, ready for a theme; null when Settings › Contact has none.
     *
     * @return array{name: string, address: ?string, phones: list<array{text: string, tel: ?string, icon: string}>, emails: list<string>, map: ?string, map_link: ?string}|null
     */
    public static function campus(array $data): ?array
    {
        $campuses = array_values((array) settings('contact.campuses'));
        $campus = $campuses[(int) ($data['campus'] ?? 0)] ?? $campuses[0] ?? null;
        if (! is_array($campus)) {
            return null;
        }
        $settings = app(Settings::class);
        $https = fn (mixed $url): ?string => is_string($url) && str_starts_with($url, 'https://') ? $url : null;

        return [
            'name' => (string) $settings->translate($campus['name'] ?? null),
            'address' => filled($address = $settings->translate($campus['address'] ?? null)) ? (string) $address : null,
            'phones' => collect((array) ($campus['phones'] ?? []))->filter()->map(fn (string $p): array => [
                'text' => $p,
                'tel' => preg_match('/(\+?[\d][\d\s-]{5,})/', $p, $m) ? preg_replace('/[^\d+]/', '', $m[1]) : null,
                'icon' => preg_match('/^\s*fax/i', $p) ? 'printer' : (preg_match('/help\s*line|hotline/i', $p) ? 'headphones' : 'phone'),
            ])->values()->all(),
            'emails' => array_values(array_filter((array) ($campus['emails'] ?? []))),
            'map' => $https($campus['map_embed'] ?? null),
            'map_link' => $https($campus['map_link'] ?? null),
        ];
    }
}
