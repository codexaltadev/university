<?php

namespace Modules\Faq\Filament\Resources\Faqs\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        // "Show in", main site and highlight fields come with the platform form components (Phase 2)
        return $schema->components([
            TextInput::make('question')->required()->maxLength(500)->columnSpanFull(),
            Textarea::make('answer')->required()->rows(5)->columnSpanFull(),
        ]);
    }
}
