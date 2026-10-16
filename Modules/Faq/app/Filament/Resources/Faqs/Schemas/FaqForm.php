<?php

namespace Modules\Faq\Filament\Resources\Faqs\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Faq\Models\Faq;
use Platform\Filament\Components\HighlightPanel;
use Platform\Filament\Components\PublishingPanel;
use Platform\Filament\Components\RelatedToField;
use Platform\Filament\Components\TermSelect;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Taxonomy\Vocabularies;

/** Standard content form (UI/UX Part A §5.2): question and answer per language left; active / show in / highlight / category right. */
class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Section::make()->columnSpan(2)->schema([
                    TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                        TextInput::make("question.{$locale}")->label(__('faq::faq.fields.question'))->required($isDefault)->maxLength(500),
                        RichEditor::make("answer.{$locale}")->label(__('faq::faq.fields.answer'))->required($isDefault)->fileAttachments(false),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    PublishingPanel::make(),
                    RelatedToField::make(),
                    HighlightPanel::make(),
                    Section::make()->compact()->schema([
                        TermSelect::make('category')->vocabulary(Faq::CATEGORY),
                        TermSelect::make('tags')->vocabulary(Vocabularies::SHARED_TAGS),
                    ]),
                ]),
            ]),
        ]);
    }
}
