<?php

namespace Modules\Admission\Blocks;

use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Platform\Pages\PageBlock;

/** "Get admission updates" by email or WhatsApp (FR-ADM-09). */
final class SubscribeBlock extends PageBlock
{
    public static function name(): string
    {
        return 'admission-subscribe';
    }

    public static function label(): string
    {
        return __('admission::admission.blocks.subscribe');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedEnvelope;
    }

    public static function view(): string
    {
        return 'admission::blocks.subscribe';
    }

    public static function schema(): array
    {
        return [
            TextInput::make('heading')->label(__('admission::admission.block.heading'))->maxLength(150),
            TextInput::make('topic')->label(__('admission::admission.fields.topic'))->default('admission')->maxLength(50)
                ->helperText(__('admission::admission.block.topic_help')),
        ];
    }
}
