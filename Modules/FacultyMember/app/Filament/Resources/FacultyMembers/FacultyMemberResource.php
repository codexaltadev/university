<?php

namespace Modules\FacultyMember\Filament\Resources\FacultyMembers;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\FacultyMember\Filament\Resources\FacultyMembers\Pages\EditFacultyMember;
use Modules\FacultyMember\Filament\Resources\FacultyMembers\Pages\ListFacultyMembers;
use Modules\FacultyMember\Filament\Resources\FacultyMembers\Schemas\FacultyMemberForm;
use Modules\FacultyMember\Filament\Resources\FacultyMembers\Tables\FacultyMembersTable;
use Modules\FacultyMember\Models\FacultyMember;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use UnitEnum;

/** People › Faculty Members (M-07, FR-FM-11): the faculty site's teachers, read only, with Sync now. */
class FacultyMemberResource extends Resource
{
    protected static ?string $model = FacultyMember::class;

    protected static string|UnitEnum|null $navigationGroup = 'People';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'faculty-members';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('facultymember::facultymember.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('facultymember::facultymember.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return FacultyMemberForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FacultyMembersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [ActivityRelationManager::class];   // FR-GEN-83
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFacultyMembers::route('/'),
            'edit' => EditFacultyMember::route('/{record}/edit'),
        ];
    }
}
