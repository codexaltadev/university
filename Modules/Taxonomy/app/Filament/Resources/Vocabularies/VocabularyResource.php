<?php

namespace Modules\Taxonomy\Filament\Resources\Vocabularies;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Taxonomy\Filament\Resources\Vocabularies\Pages\EditVocabulary;
use Modules\Taxonomy\Filament\Resources\Vocabularies\Pages\ListVocabularies;
use Modules\Taxonomy\Filament\Resources\Vocabularies\RelationManagers\TermsRelationManager;
use Modules\Taxonomy\Filament\Resources\Vocabularies\Schemas\VocabularyForm;
use Modules\Taxonomy\Filament\Resources\Vocabularies\Tables\VocabulariesTable;
use Platform\Taxonomy\Vocabularies;
use Platform\Taxonomy\Vocabulary;

/** Site › Categories & Tags (C-16): vocabularies come from active modules; their terms are edited here. */
class VocabularyResource extends Resource
{
    protected static ?string $model = Vocabulary::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'taxonomy';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return 'Site';
    }

    public static function getNavigationLabel(): string
    {
        return __('taxonomy::taxonomy.title');
    }

    public static function getModelLabel(): string
    {
        return __('taxonomy::taxonomy.vocabulary');
    }

    public static function getPluralModelLabel(): string
    {
        return __('taxonomy::taxonomy.title');
    }

    /** Only vocabularies of active modules (and the shared ones). */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('key', array_keys(app(Vocabularies::class)->definitions()));
    }

    public static function form(Schema $schema): Schema
    {
        return VocabularyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VocabulariesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [TermsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVocabularies::route('/'),
            'edit' => EditVocabulary::route('/{record}/edit'),
        ];
    }
}
