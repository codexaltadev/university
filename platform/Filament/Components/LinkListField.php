<?php

namespace Platform\Filament\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

/**
 * A list of links a page shows (Quick Links, side links): text per language and an address,
 * in order. Stored as [{label: {en, bn}, url}]; the website reads it with link_list().
 *
 *     LinkListField::make('quick_links')->label(__('faculty::faculty.fields.quick_links'))
 */
class LinkListField
{
    public static function make(string $name): Repeater
    {
        return Repeater::make($name)
            ->schema([
                Grid::make(2)->schema([
                    ...TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                        TextInput::make("label.{$locale}")->label(__('platform::relations.link_text')." ({$language})")->required($isDefault)->maxLength(100),
                    ]),
                    TextInput::make('url')->label(__('platform::relations.link_url'))->helperText(__('platform::relations.link_url_help'))
                        ->required()->maxLength(2048)->regex('#^(https?://\S+|/\S*|mailto:\S+|tel:\S+)$#i'),
                ]),
            ])
            ->addActionLabel(__('platform::relations.add_link'))
            ->reorderable()->collapsible()->cloneable()
            ->itemLabel(fn (array $state): ?string => collect($state['label'] ?? [])->first(fn ($v) => filled($v)))
            ->defaultItems(0);
    }
}
