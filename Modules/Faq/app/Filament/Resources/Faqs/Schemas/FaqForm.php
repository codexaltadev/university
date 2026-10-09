<?php

namespace Modules\Faq\Filament\Resources\Faqs\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Platform\Filament\Components\HighlightPanel;
use Platform\Filament\Components\PublishingPanel;
use Platform\Filament\Components\RelatedToField;
use Platform\Filament\Components\TermSelect;
use Platform\Taxonomy\Vocabularies;

/** Standard content form (UI/UX Part A §5.2): content left, publishing / show in / highlight / category right. */
class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Section::make()->columnSpan(2)->schema([
                    TextInput::make('question')->required()->maxLength(500),
                    Textarea::make('answer')->required()->rows(8),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    PublishingPanel::make(),
                    RelatedToField::make(),
                    HighlightPanel::make(),
                    Section::make()->compact()->schema([
                        TermSelect::make('category')->vocabulary('faq.category'),
                        TermSelect::make('tags')->vocabulary(Vocabularies::SHARED_TAGS),
                    ]),
                ]),
            ]),
        ]);
    }
}
