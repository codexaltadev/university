<?php

namespace Modules\Page\Filament\Resources\Pages;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Page\Filament\Resources\Pages\Pages\CreatePage;
use Modules\Page\Filament\Resources\Pages\Pages\EditPage;
use Modules\Page\Filament\Resources\Pages\Pages\ListPages;
use Modules\Page\Filament\Resources\Pages\Schemas\PageForm;
use Modules\Page\Filament\Resources\Pages\Tables\PagesTable;
use Modules\Page\Models\Page;
use Platform\Filament\RelationManagers\ActivityRelationManager;

/** Content › Pages (C-11): main site and microsite pages. */
class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationGroup(): ?string
    {
        return 'Content';
    }

    public static function getModelLabel(): string
    {
        return __('page::page.page');
    }

    public static function getPluralModelLabel(): string
    {
        return __('page::page.pages');
    }

    /** Trash and restore need soft-deleted rows (FR-GEN-02). */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return PageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [ActivityRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
