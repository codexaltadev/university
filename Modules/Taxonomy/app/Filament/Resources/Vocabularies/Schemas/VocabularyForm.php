<?php

namespace Modules\Taxonomy\Filament\Resources\Vocabularies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Taxonomy\Vocabulary;

/** Only the name can change; key, owner and structure come from the module (FR-TAX-01). */
class VocabularyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->columnSpanFull()->schema([
                TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                    TextInput::make("name.{$locale}")->label(__('taxonomy::taxonomy.fields.name'))->required($isDefault)->maxLength(100),
                ]),
                TextInput::make('key')->label(__('taxonomy::taxonomy.fields.key'))->disabled()->dehydrated(false),
                TextInput::make('module_slug')->label(__('taxonomy::taxonomy.fields.module'))
                    ->formatStateUsing(fn (?string $state): string => $state ?? __('taxonomy::taxonomy.shared'))
                    ->disabled()->dehydrated(false),
                TextInput::make('structure')->label(__('taxonomy::taxonomy.fields.structure'))
                    ->formatStateUsing(fn (?Vocabulary $record): string => $record ? static::structure($record) : '')
                    ->disabled()->dehydrated(false)->columnSpanFull(),
            ]),
        ]);
    }

    public static function structure(Vocabulary $vocabulary): string
    {
        return implode(' · ', [
            __($vocabulary->is_hierarchical ? 'taxonomy::taxonomy.nested' : 'taxonomy::taxonomy.flat'),
            __($vocabulary->allows_multiple ? 'taxonomy::taxonomy.multiple' : 'taxonomy::taxonomy.single'),
        ]);
    }
}
