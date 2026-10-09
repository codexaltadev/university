<?php

namespace Modules\Media\Filament\Resources\MediaItems;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Modules\Media\Filament\Resources\MediaItems\Pages\EditMediaItem;
use Modules\Media\Filament\Resources\MediaItems\Pages\ListMediaItems;
use Modules\Media\Filament\Resources\MediaItems\Schemas\MediaItemForm;
use Modules\Media\Filament\Resources\MediaItems\Tables\MediaItemsTable;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use Platform\Media\MediaItem;

/** Site › Media Library (C-07): every file of the website in one place. */
class MediaItemResource extends Resource
{
    protected static ?string $model = MediaItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'media';

    public static function getNavigationGroup(): ?string
    {
        return 'Site';
    }

    public static function getNavigationLabel(): string
    {
        return __('media::media.title');
    }

    public static function getModelLabel(): string
    {
        return __('media::media.item');
    }

    public static function getPluralModelLabel(): string
    {
        return __('media::media.title');
    }

    public static function getRecordTitle($record): string
    {
        return $record?->title ?: (string) $record?->fileName();
    }

    /** Only media this user may see (FR-MEDIA-14); trash included for the Trash filter. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->visibleTo(Auth::user())
            ->with(['media', 'creator', 'folder']);
    }

    public static function form(Schema $schema): Schema
    {
        return MediaItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MediaItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [ActivityRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMediaItems::route('/'),
            'edit' => EditMediaItem::route('/{record}/edit'),
        ];
    }
}
