<?php

namespace Modules\Transport\Settings;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Transport (DB-06 v1.1 §2). The live transport system's API (address, how long
 * its answer is kept, how long to wait) and the page's own text that is not in the API: the
 * introduction, the "Transport Pass" part and the fees table — defaults are DIU's current page.
 * Read: settings('transport.api_url'), settings('transport.intro') (current language) …
 */
class TransportSettings extends ModuleSettings
{
    public const DEFAULT_URL = 'https://webbackend.daffodilvarsity.edu.bd/api/v2/public/transport';

    public function group(): string
    {
        return 'transport';
    }

    public function label(): string
    {
        return __('transport::transport.title');
    }

    public function defaults(): array
    {
        return [
            'api_url' => self::DEFAULT_URL,
            'cache_minutes' => 30,
            'timeout' => 8,
            'intro' => ['en' => 'From Daffodil Smart City, Daffodil International University operates a safe, reliable, and affordable transport system with 50+ dedicated buses, smart route management, and emergency support — ensuring smooth, worry-free commutes for students, faculty, and staff.'],
            'pass_title' => ['en' => 'Get Your DIU Transport Pass'],
            'pass_subtitle' => ['en' => 'A secure pass that lets you ride DIU\'s buses easily.'],
            'pass_how' => ['en' => 'Pay the transport fee, then register online via the student portal or offline at the Transport Office. Your pass will be issued once approved.'],
            'pass_why' => ['en' => 'Required for boarding DIU buses — ensures student verification and smooth, secure commutes.'],
            'pass_image' => null,
            'fees' => [
                ['route' => 'Dhanmondi', 'tri' => '3000/=tk', 'bi' => '4500/=tk'],
                ['route' => 'Uttara', 'tri' => '3000/=tk', 'bi' => '4500/=tk'],
                ['route' => 'ECB-Mirpur', 'tri' => '3000/=tk', 'bi' => '4500/=tk'],
            ],
            'fees_note' => null,
        ];
    }

    public function translatable(): array
    {
        return ['intro', 'pass_title', 'pass_subtitle', 'pass_how', 'pass_why', 'fees_note'];
    }

    public function schema(): array
    {
        return [
            Section::make(__('transport::transport.settings.api'))->compact()->schema([
                TextInput::make('api_url')->label(__('transport::transport.settings.api_url'))->helperText(__('transport::transport.settings.api_url_help'))
                    ->url()->required()->maxLength(500),
                Grid::make(2)->schema([
                    TextInput::make('cache_minutes')->label(__('transport::transport.settings.cache_minutes'))->integer()->minValue(1)->maxValue(1440)->required(),
                    TextInput::make('timeout')->label(__('transport::transport.settings.timeout'))->integer()->minValue(2)->maxValue(30)->suffix('s')->required(),
                ]),
            ]),
            Section::make(__('transport::transport.settings.page'))->compact()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    Textarea::make("intro.{$locale}")->label(__('transport::transport.settings.intro'))->rows(3)->maxLength(1000),
                    TextInput::make("pass_title.{$locale}")->label(__('transport::transport.settings.pass_title'))->maxLength(150),
                    TextInput::make("pass_subtitle.{$locale}")->label(__('transport::transport.settings.pass_subtitle'))->maxLength(255),
                    Textarea::make("pass_how.{$locale}")->label(__('transport::transport.pass.how'))->rows(2)->maxLength(600),
                    Textarea::make("pass_why.{$locale}")->label(__('transport::transport.pass.why'))->rows(2)->maxLength(600),
                    Textarea::make("fees_note.{$locale}")->label(__('transport::transport.settings.fees_note'))->rows(2)->maxLength(600),
                ]),
                MediaPicker::make('pass_image')->label(__('transport::transport.settings.pass_image'))->image()->storeIds(),
                Repeater::make('fees')->label(__('transport::transport.fees.title'))->helperText(__('transport::transport.settings.fees_help'))
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('route')->label(__('transport::transport.fees.route'))->required()->maxLength(100),
                            TextInput::make('tri')->label(__('transport::transport.fees.tri'))->maxLength(50),
                            TextInput::make('bi')->label(__('transport::transport.fees.bi'))->maxLength(50),
                        ]),
                    ])
                    ->reorderable()->collapsible()->defaultItems(0)->itemLabel(fn (array $state): ?string => $state['route'] ?? null),
            ]),
        ];
    }
}
