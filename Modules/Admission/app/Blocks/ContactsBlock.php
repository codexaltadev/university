<?php

namespace Modules\Admission\Blocks;

use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Modules\Admission\Models\AdmissionContactGroup;
use Platform\Pages\PageBlock;

/** Admission contacts, grouped (FR-ADM-10). */
final class ContactsBlock extends PageBlock
{
    public static function name(): string
    {
        return 'admission-contacts';
    }

    public static function label(): string
    {
        return __('admission::admission.blocks.contacts');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedPhone;
    }

    public static function view(): string
    {
        return 'admission::blocks.contacts';
    }

    public static function schema(): array
    {
        return [
            Select::make('groups')->label(__('admission::admission.block.groups'))->multiple()
                ->options(fn (): array => AdmissionContactGroup::query()->ordered()->get()->mapWithKeys(fn (AdmissionContactGroup $g): array => [$g->id => $g->name])->all())
                ->helperText(__('admission::admission.block.groups_help')),
        ];
    }
}
