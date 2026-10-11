<?php

namespace Modules\Admission\Filament\Resources\ResultBatches;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
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
use Modules\Admission\Filament\Resources\ResultBatches\Pages\CreateResultBatch;
use Modules\Admission\Filament\Resources\ResultBatches\Pages\EditResultBatch;
use Modules\Admission\Filament\Resources\ResultBatches\Pages\ListResultBatches;
use Modules\Admission\Filament\Resources\ResultBatches\RelationManagers\ResultsRelationManager;
use Modules\Admission\Models\ResultBatch;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\RelationManagers\ActivityRelationManager;

/**
 * Admission › Test results (FR-ADM-05/06/08): one batch per admission test — its messages,
 * "Published" switch and the imported results (Import on the edit page).
 */
class ResultBatchResource extends Resource
{
    protected static ?string $model = ResultBatch::class;

    protected static ?string $cluster = AdmissionCluster::class;

    protected static ?string $slug = 'results';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('admission::admission.batch.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admission::admission.batch.plural');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Section::make(__('admission::admission.sections.messages'))->columnSpan(2)
                    ->description(__('admission::admission.help.messages'))
                    ->schema([
                        TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                            TextInput::make("name.{$locale}")->label(__('admission::admission.fields.name'))->required($isDefault)->maxLength(150),
                            ...collect(ResultBatch::MESSAGES)->map(fn (string $key) => Textarea::make("messages.{$key}.{$locale}")
                                ->label(__("admission::admission.messages.{$key}"))->rows(3)->maxLength(2000))->all(),
                        ]),
                    ]),
                Section::make()->columnSpan(1)->compact()->schema([
                    TextInput::make('session_label')->label(__('admission::admission.fields.session'))
                        ->helperText(__('admission::admission.help.session'))->maxLength(50),
                    Toggle::make('is_active')->label(__('admission::admission.fields.published'))
                        ->helperText(__('admission::admission.help.published'))->default(false),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('admission::admission.fields.name'))->searchable()
                    ->description(fn (ResultBatch $record): ?string => $record->session_label),
                TextColumn::make('imported_rows')->label(__('admission::admission.fields.rows'))->numeric(),
                TextColumn::make('imported_at')->label(__('admission::admission.fields.imported_at'))->dateTime('d M Y H:i')->placeholder('—'),
                ToggleColumn::make('is_active')->label(__('admission::admission.fields.published'))
                    ->disabled(fn ($record): bool => ! auth()->user()?->can('update', $record)),
            ])
            ->filters([TrashedFilter::make()])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }

    public static function getRelations(): array
    {
        return [ResultsRelationManager::class, ActivityRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListResultBatches::route('/'),
            'create' => CreateResultBatch::route('/create'),
            'edit' => EditResultBatch::route('/{record}/edit'),
        ];
    }
}
