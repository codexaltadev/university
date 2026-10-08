<?php

namespace Modules\Faq\Filament\Resources\Faqs;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Faq\Filament\Resources\Faqs\Pages\CreateFaq;
use Modules\Faq\Filament\Resources\Faqs\Pages\EditFaq;
use Modules\Faq\Filament\Resources\Faqs\Pages\ListFaqs;
use Modules\Faq\Filament\Resources\Faqs\Schemas\FaqForm;
use Modules\Faq\Filament\Resources\Faqs\Tables\FaqsTable;
use Modules\Faq\Models\Faq;
use UnitEnum;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?int $navigationSort = 90;

    protected static ?string $recordTitleAttribute = 'question';

    public static function form(Schema $schema): Schema
    {
        return FaqForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FaqsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFaqs::route('/'),
            'create' => CreateFaq::route('/create'),
            'edit' => EditFaq::route('/{record}/edit'),
        ];
    }
}
