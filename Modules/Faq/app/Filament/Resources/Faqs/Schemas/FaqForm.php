<?php

namespace Modules\Faq\Filament\Resources\Faqs\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Platform\Filament\Components\TermSelect;
use Platform\Taxonomy\Vocabularies;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        // "Show in", main site and highlight fields come with the platform form components (Phase 2)
        return $schema->components([
            TextInput::make('question')->required()->maxLength(500)->columnSpanFull(),
            Textarea::make('answer')->required()->rows(5)->columnSpanFull(),
            TermSelect::make('category')->vocabulary('faq.category'),
            TermSelect::make('tags')->vocabulary(Vocabularies::SHARED_TAGS),
        ]);
    }
}
