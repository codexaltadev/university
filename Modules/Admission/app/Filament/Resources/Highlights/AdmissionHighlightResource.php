<?php

namespace Modules\Admission\Filament\Resources\Highlights;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Admission\Filament\Clusters\AdmissionCluster;
use Modules\Admission\Filament\Resources\Highlights\Pages\ManageAdmissionHighlights;
use Modules\Admission\Models\AdmissionHighlight;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TermSelect;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Taxonomy\Vocabularies;

/** Admission › Information cards: picture, title, link, by category (Notice, Facility, Campus Life …). */
class AdmissionHighlightResource extends Resource
{
    protected static ?string $model = AdmissionHighlight::class;

    protected static ?string $cluster = AdmissionCluster::class;

    protected static ?string $slug = 'information';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('admission::admission.highlight.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admission::admission.highlight.plural');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TranslatableTabs::make()->columnSpanFull()->schema(fn (string $locale, bool $isDefault): array => [
                TextInput::make("title.{$locale}")->label(__('admission::admission.fields.title'))->required($isDefault)->maxLength(255),
            ]),
            TermSelect::make('category')->vocabulary(AdmissionHighlight::CATEGORY),
            TextInput::make('url')->label(__('admission::admission.fields.url'))
                ->regex('#^(https?://|/)#')->maxLength(2000)->helperText(__('admission::admission.help.url')),
            MediaPicker::make('image')->label(__('admission::admission.fields.image'))->image(),
            Toggle::make('open_in_new_tab')->label(__('admission::admission.fields.new_tab')),
            Toggle::make('is_active')->label(__('platform::relations.active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('terms'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('title')->label(__('admission::admission.fields.title'))->searchable()->wrap()
                    ->description(fn (AdmissionHighlight $record): ?string => $record->url),
                TextColumn::make('category')->label(__('admission::admission.fields.category'))->badge()
                    ->state(fn (AdmissionHighlight $record): ?string => $record->termsIn(AdmissionHighlight::CATEGORY)->first()?->name),
                ToggleColumn::make('is_active')->label(__('platform::relations.active'))
                    ->disabled(fn ($record): bool => ! auth()->user()?->can('update', $record)),
            ])
            ->filters([
                SelectFilter::make('category')->label(__('admission::admission.fields.category'))
                    ->options(fn (): array => app(Vocabularies::class)->options(AdmissionHighlight::CATEGORY))
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null) ? $query->withTerm((int) $data['value']) : $query),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAdmissionHighlights::route('/')];
    }
}
