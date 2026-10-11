<?php

namespace Modules\Admission\Blocks;

use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Platform\Pages\PageBlock;

/** Apply Now / Request for Information / WhatsApp group buttons from Settings › Admission (FR-ADM-04). */
final class ApplyBlock extends PageBlock
{
    public static function name(): string
    {
        return 'admission-apply';
    }

    public static function label(): string
    {
        return __('admission::admission.blocks.apply');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedCursorArrowRays;
    }

    public static function view(): string
    {
        return 'admission::blocks.apply';
    }

    public static function schema(): array
    {
        return [
            TextInput::make('heading')->label(__('admission::admission.block.heading'))->maxLength(150),
            Textarea::make('text')->label(__('admission::admission.block.text'))->rows(2)->maxLength(500),
            CheckboxList::make('buttons')->label(__('admission::admission.block.buttons'))->default(['apply', 'info'])
                ->options(['apply' => __('admission::admission.apply_now'), 'info' => __('admission::admission.request_info'), 'whatsapp' => __('admission::admission.join_whatsapp'), 'result' => __('admission::admission.result_page.title')])
                ->helperText(__('admission::admission.block.buttons_help')),
        ];
    }
}
