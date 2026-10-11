<?php

namespace Modules\Admission\Filament\Resources\Circulars;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
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
use Modules\Admission\Filament\Resources\Circulars\Pages\CreateCircular;
use Modules\Admission\Filament\Resources\Circulars\Pages\EditCircular;
use Modules\Admission\Filament\Resources\Circulars\Pages\ListCirculars;
use Modules\Admission\Models\Circular;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\SeoPanel;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\Components\WebAddressField;
use Platform\Filament\RelationManagers\ActivityRelationManager;

/** Admission › Circulars (FR-ADM-02/03): a session's admission and its important dates. */
class CircularResource extends Resource
{
    protected static ?string $model = Circular::class;

    protected static ?string $cluster = AdmissionCluster::class;

    protected static ?string $slug = 'circulars';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('admission::admission.circular.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admission::admission.circular.plural');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Section::make()->columnSpan(2)->schema([
                    TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                        TextInput::make("title.{$locale}")->label(__('admission::admission.fields.title'))->required($isDefault)->maxLength(255),
                        RichEditor::make("content.{$locale}")->label(__('admission::admission.fields.content'))->fileAttachments(false),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    Section::make(__('admission::admission.sections.dates'))->compact()->schema([
                        TextInput::make('session_label')->label(__('admission::admission.fields.session'))
                            ->helperText(__('admission::admission.help.session'))->maxLength(50),
                        ...collect(Circular::DATES)->map(fn (string $date) => DatePicker::make($date)->label(__("admission::admission.dates.{$date}")))->all(),
                    ]),
                    Section::make(__('admission::admission.sections.page'))->compact()->schema([
                        WebAddressField::make(prefix: '/'.Circular::PATH.'/'),
                        MediaPicker::make('pdf')->label(__('admission::admission.fields.pdf')),
                        Toggle::make('is_active')->label(__('platform::relations.active'))->default(true),
                    ]),
                    SeoPanel::make(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('title')->label(__('admission::admission.fields.title'))->searchable()->wrap()
                    ->description(fn (Circular $record): ?string => $record->session_label),
                TextColumn::make('apply_ends_on')->label(__('admission::admission.dates.apply_ends_on'))->date('d M Y')->sortable()
                    ->badge()->color(fn (Circular $record): string => $record->isClosed() ? 'gray' : 'success')
                    ->description(fn (Circular $record): ?string => $record->apply_ends_on ? __($record->isClosed() ? 'admission::admission.closed' : 'admission::admission.open') : null),
                TextColumn::make('test_on')->label(__('admission::admission.dates.test_on'))->date('d M Y')->toggleable(),
                ToggleColumn::make('is_active')->label(__('platform::relations.active'))
                    ->disabled(fn ($record): bool => ! auth()->user()?->can('update', $record)),
            ])
            ->filters([TrashedFilter::make()])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }

    public static function getRelations(): array
    {
        return [ActivityRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCirculars::route('/'),
            'create' => CreateCircular::route('/create'),
            'edit' => EditCircular::route('/{record}/edit'),
        ];
    }
}
