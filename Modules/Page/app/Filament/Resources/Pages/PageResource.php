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
use Platform\Context\MicrositeTool;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use Platform\Filament\Tables\ContentFilters;

/** Content › Pages (C-11): main site and microsite pages. */
class PageResource extends Resource implements MicrositeTool
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    /** Microsite tool (UI/UX Part A §4.1): the owner's pages. */
    public static function canUseFor(string $ownerKey): bool
    {
        return static::canViewAny() && in_array($ownerKey, ContentFilters::ownerKeys(), true);
    }

    public static function urlFor(string $ownerKey): string
    {
        return static::getUrl('index', ['filters' => ['owner' => ['value' => $ownerKey]]]);
    }

    public static function isSetUpFor(string $ownerKey): bool
    {
        return Page::query()->ownedBy($ownerKey)->exists();
    }

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
