<?php

namespace Modules\Transport\Settings;

use Filament\Forms\Components\TextInput;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Transport (DB-06 v1.1 §2): where the live transport system's public API is,
 * how long its answer is kept, how long to wait for it. Read: settings('transport.api_url') …
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
        return ['api_url' => self::DEFAULT_URL, 'cache_minutes' => 30, 'timeout' => 8];
    }

    public function schema(): array
    {
        return [
            TextInput::make('api_url')->label(__('transport::transport.settings.api_url'))->helperText(__('transport::transport.settings.api_url_help'))
                ->url()->required()->maxLength(500),
            TextInput::make('cache_minutes')->label(__('transport::transport.settings.cache_minutes'))->integer()->minValue(1)->maxValue(1440)->required(),
            TextInput::make('timeout')->label(__('transport::transport.settings.timeout'))->integer()->minValue(2)->maxValue(30)->suffix('s')->required(),
        ];
    }
}
