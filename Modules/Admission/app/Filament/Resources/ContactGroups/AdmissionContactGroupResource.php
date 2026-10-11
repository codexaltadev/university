<?php

namespace Modules\Admission\Filament\Resources\ContactGroups;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Admission\Filament\Clusters\AdmissionCluster;
use Modules\Admission\Filament\Resources\ContactGroups\Pages\CreateAdmissionContactGroup;
use Modules\Admission\Filament\Resources\ContactGroups\Pages\EditAdmissionContactGroup;
use Modules\Admission\Filament\Resources\ContactGroups\Pages\ListAdmissionContactGroups;
use Modules\Admission\Models\AdmissionContactGroup;
use Platform\Filament\Components\PeopleManager;
use Platform\Filament\Components\TranslatableTabs;

/** Admission › Contacts (FR-ADM-10): groups ("Local Admission", "International Admission") and their people. */
class AdmissionContactGroupResource extends Resource
{
    protected static ?string $model = AdmissionContactGroup::class;

    protected static ?string $cluster = AdmissionCluster::class;

    protected static ?string $slug = 'contacts';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return __('admission::admission.contact_group.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admission::admission.contact_group.plural');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->columnSpanFull()->schema([
                TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                    TextInput::make("name.{$locale}")->label(__('admission::admission.fields.name'))->required($isDefault)->maxLength(150),
                ]),
                Toggle::make('is_active')->label(__('platform::relations.active'))->default(true),
            ]),
            PeopleManager::make()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label(__('admission::admission.fields.name')),
                TextColumn::make('assignments_count')->counts('assignments')->label(__('admission::admission.fields.people')),
                ToggleColumn::make('is_active')->label(__('platform::relations.active'))
                    ->disabled(fn ($record): bool => ! auth()->user()?->can('update', $record)),
            ])
            ->filters([TrashedFilter::make()])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdmissionContactGroups::route('/'),
            'create' => CreateAdmissionContactGroup::route('/create'),
            'edit' => EditAdmissionContactGroup::route('/{record}/edit'),
        ];
    }
}
