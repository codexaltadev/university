<?php

namespace Modules\Admission\Blocks;

use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;
use Platform\Pages\PageBlock;

/** Admission circulars with their important dates and countdown, open ones first (FR-ADM-01/02, FR-ADM-15). */
final class CircularsBlock extends PageBlock
{
    public static function name(): string
    {
        return 'admission-circulars';
    }

    public static function label(): string
    {
        return __('admission::admission.blocks.circulars');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedMegaphone;
    }

    public static function view(): string
    {
        return 'admission::blocks.circulars';
    }

    public static function schema(): array
    {
        return [
            Toggle::make('running_only')->label(__('admission::admission.block.running_only'))->default(true),
            TextInput::make('limit')->label(__('admission::admission.block.limit'))->integer()->minValue(1)->maxValue(20)->default(3),
        ];
    }
}
