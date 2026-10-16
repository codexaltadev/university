<?php

namespace Modules\Alumni\Filament\Resources\Alumni;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Alumni\Filament\Resources\Alumni\Pages\CreateAlumnus;
use Modules\Alumni\Filament\Resources\Alumni\Pages\EditAlumnus;
use Modules\Alumni\Filament\Resources\Alumni\Pages\ListAlumni;
use Modules\Alumni\Models\Alumnus;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\SeoPanel;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\Components\WebAddressField;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use UnitEnum;

/**
 * People › Alumni (M-17, FR-ALUM-01..09): name, what they do and story per language; where they
 * studied; contacts each public or not; Pride; a visitor's submission comes in off and is
 * listed under "New submissions" until it is checked (FR-ALUM-07).
 */
class AlumnusResource extends Resource
{
    protected static ?string $model = Alumnus::class;

    protected static string|UnitEnum|null $navigationGroup = 'People';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?int $navigationSort = 24;

    protected static ?string $slug = 'alumni';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('alumni::alumni.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('alumni::alumni.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $new = Alumnus::query()->newSubmissions()->count();

        return $new > 0 ? (string) $new : null;
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Grid::make(1)->columnSpan(2)->schema([
                    Section::make()->schema([
                        TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                            TextInput::make("name.{$locale}")->label(__('alumni::alumni.fields.name'))->required($isDefault)->maxLength(150),
                            Grid::make(2)->schema([
                                TextInput::make("current_designation.{$locale}")->label(__('alumni::alumni.fields.current_designation'))->maxLength(191),
                                TextInput::make("organization.{$locale}")->label(__('alumni::alumni.fields.organization'))->maxLength(191),
                            ]),
                            TextInput::make("office_address.{$locale}")->label(__('alumni::alumni.fields.office_address'))->maxLength(255),
                            RichEditor::make("story.{$locale}")->label(__('alumni::alumni.fields.story'))->fileAttachments(false),
                        ]),
                    ]),
                    Section::make(__('alumni::alumni.sections.study'))->compact()->schema([
                        Grid::make(4)->schema([
                            Select::make('department_id')->label(__('alumni::alumni.fields.department'))->searchable()
                                ->options(fn (): array => static::options(Alumnus::DEPARTMENT))->visible(fn (): bool => class_exists(Alumnus::DEPARTMENT))
                                ->helperText(__('alumni::alumni.help.department')),
                            Select::make('program_id')->label(__('alumni::alumni.fields.program'))->searchable()
                                ->options(fn (): array => static::options(Alumnus::PROGRAM))->visible(fn (): bool => class_exists(Alumnus::PROGRAM)),
                            TextInput::make('batch')->label(__('alumni::alumni.fields.batch'))->maxLength(30),
                            TextInput::make('passing_year')->label(__('alumni::alumni.fields.passing_year'))->integer()->minValue(1990)->maxValue((int) date('Y') + 1),
                            TextInput::make('student_id')->label(__('alumni::alumni.fields.student_id'))->maxLength(30),
                            Select::make('job_type')->label(__('alumni::alumni.fields.job_type'))->options(static::jobTypes()),
                        ]),
                    ]),
                    Section::make(__('alumni::alumni.sections.contact'))->description(__('alumni::alumni.help.contact'))->compact()->schema([
                        Grid::make(3)->schema([
                            TextInput::make('email')->label(__('alumni::alumni.fields.email'))->email()->maxLength(191),
                            TextInput::make('phone')->label(__('alumni::alumni.fields.phone'))->tel()->maxLength(50),
                            Toggle::make('show_address')->label(__('alumni::alumni.fields.show_address'))->inline(false),
                            Toggle::make('show_email')->label(__('alumni::alumni.fields.public')),
                            Toggle::make('show_phone')->label(__('alumni::alumni.fields.public')),
                        ]),
                        Grid::make(3)->schema(collect(Alumnus::SOCIAL)->map(fn (string $s) => TextInput::make("social_links.{$s}")->label(__("alumni::alumni.social.{$s}"))->url()->maxLength(255))->all()),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    Section::make()->compact()->schema([
                        Toggle::make('is_active')->label(__('platform::relations.active'))->default(true)
                            ->helperText(fn (?Alumnus $record): ?string => $record?->submission_source === 'public_form' && ! $record->is_active ? __('alumni::alumni.help.submission') : null),
                        Toggle::make('is_pride')->label(__('alumni::alumni.fields.is_pride'))->helperText(__('alumni::alumni.help.is_pride')),
                        WebAddressField::make(prefix: '/'.Alumnus::PATH.'/'),
                    ]),
                    Section::make(__('alumni::alumni.sections.media'))->compact()->schema([
                        MediaPicker::make('photo')->label(__('alumni::alumni.fields.photo'))->image(),
                        TextInput::make('video_url')->label(__('alumni::alumni.fields.video_url'))->url()->maxLength(2048)
                            ->helperText(__('alumni::alumni.help.video_url')),
                    ]),
                    SeoPanel::make(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                ImageColumn::make('photo')->label('')->circular()->imageSize(36)
                    ->state(fn (Alumnus $record): ?string => $record->firstMedia('photo')?->url('thumb')),
                TextColumn::make('name')->label(__('alumni::alumni.fields.name'))->wrap()
                    ->searchable(query: fn (Builder $query, string $search) => $query->where(function (Builder $w) use ($search): void {
                        $like = '%'.mb_strtolower(addcslashes($search, '%_\\')).'%';
                        foreach (['name', 'current_designation', 'organization', 'student_id', 'email'] as $column) {
                            $w->orWhereRaw('LOWER(CAST('.$column.' AS CHAR)) LIKE ?', [$like]);
                        }
                    }))
                    ->description(fn (Alumnus $record): ?string => $record->headline()),
                TextColumn::make('batch')->label(__('alumni::alumni.fields.batch'))->toggleable(),
                TextColumn::make('passing_year')->label(__('alumni::alumni.fields.passing_year'))->sortable(),
                IconColumn::make('is_pride')->label(__('alumni::alumni.fields.is_pride'))->boolean(),
                TextColumn::make('submission_source')->label('')->badge()
                    ->state(fn (Alumnus $record): ?string => $record->submission_source === 'public_form' && ! $record->is_active ? __('alumni::alumni.new') : null),
                ToggleColumn::make('is_active')->label(__('platform::relations.active'))
                    ->disabled(fn ($record): bool => ! auth()->user()?->can('update', $record)),
            ])
            ->filters([
                Filter::make('new')->label(__('alumni::alumni.new_submissions'))->query(fn (Builder $query) => $query->newSubmissions()),
                TernaryFilter::make('is_pride')->label(__('alumni::alumni.fields.is_pride')),
                SelectFilter::make('department_id')->label(__('alumni::alumni.fields.department'))
                    ->options(fn (): array => static::options(Alumnus::DEPARTMENT))->visible(fn (): bool => class_exists(Alumnus::DEPARTMENT)),
                SelectFilter::make('program_id')->label(__('alumni::alumni.fields.program'))
                    ->options(fn (): array => static::options(Alumnus::PROGRAM))->visible(fn (): bool => class_exists(Alumnus::PROGRAM)),
                SelectFilter::make('job_type')->label(__('alumni::alumni.fields.job_type'))->options(static::jobTypes()),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('approve')->label(__('alumni::alumni.approve'))->icon(Heroicon::OutlinedCheck)->color('success')
                    ->visible(fn (Alumnus $record): bool => ! $record->is_active && (bool) auth()->user()?->can('update', $record))
                    ->action(fn (Alumnus $record) => $record->update(['is_active' => true])),
                EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make(),
            ]);
    }

    /** @return array<string, string> */
    public static function jobTypes(): array
    {
        return collect(Alumnus::JOB_TYPES)->mapWithKeys(fn (string $t): array => [$t => __("alumni::alumni.job_types.{$t}")])->all();
    }

    /** @return array<int, string> */
    protected static function options(string $class): array
    {
        return class_exists($class) ? $class::query()->ordered()->get()->mapWithKeys(fn ($m): array => [$m->id => $m->name])->all() : [];
    }

    public static function getRelations(): array
    {
        return [ActivityRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAlumni::route('/'),
            'create' => CreateAlumnus::route('/create'),
            'edit' => EditAlumnus::route('/{record}/edit'),
        ];
    }
}
