<?php

namespace Modules\Administration\Filament\Resources\Members;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Administration\Filament\Resources\Members\Pages\CreateMember;
use Modules\Administration\Filament\Resources\Members\Pages\EditMember;
use Modules\Administration\Filament\Resources\Members\Pages\ListMembers;
use Modules\Administration\Filament\Resources\Members\Schemas\MemberForm;
use Modules\Administration\Filament\Resources\Members\Tables\MembersTable;
use Modules\Administration\Models\Member;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use UnitEnum;

/** People › Administration (M-13, FR-ADMN-01): everyone but teachers. */
class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static string|UnitEnum|null $navigationGroup = 'People';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'administration';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('administration::administration.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('administration::administration.plural');
    }

    /** Trash and restore need soft-deleted rows (FR-GEN-02). */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return MemberForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MembersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [ActivityRelationManager::class];   // FR-GEN-83
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMembers::route('/'),
            'create' => CreateMember::route('/create'),
            'edit' => EditMember::route('/{record}/edit'),
        ];
    }
}
