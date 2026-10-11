<?php

namespace Modules\Admission\Filament\Resources\Steps;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Admission\Filament\Clusters\AdmissionCluster;
use Modules\Admission\Filament\Resources\Steps\Pages\ManageAdmissionSteps;
use Modules\Admission\Models\AdmissionStep;
use Platform\Filament\Components\TranslatableTabs;

/** Admission › How to apply: the steps, in order (FR-ADM-01). */
class AdmissionStepResource extends Resource
{
    protected static ?string $model = AdmissionStep::class;

    protected static ?string $cluster = AdmissionCluster::class;

    protected static ?string $slug = 'steps';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('admission::admission.step.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admission::admission.step.plural');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TranslatableTabs::make()->columnSpanFull()->schema(fn (string $locale, bool $isDefault): array => [
                TextInput::make("title.{$locale}")->label(__('admission::admission.fields.title'))->required($isDefault)->maxLength(150),
                Textarea::make("description.{$locale}")->label(__('admission::admission.fields.description'))->rows(3)->maxLength(1000),
            ]),
            TextInput::make('icon')->label(__('admission::admission.fields.icon'))->maxLength(100)
                ->helperText(__('admission::admission.help.icon')),
            Toggle::make('is_active')->label(__('platform::relations.active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('title')->label(__('admission::admission.fields.title'))->wrap()
                    ->description(fn (AdmissionStep $record): ?string => $record->description),
                ToggleColumn::make('is_active')->label(__('platform::relations.active'))
                    ->disabled(fn ($record): bool => ! auth()->user()?->can('update', $record)),
            ])
            ->filters([TrashedFilter::make()])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAdmissionSteps::route('/')];
    }
}
