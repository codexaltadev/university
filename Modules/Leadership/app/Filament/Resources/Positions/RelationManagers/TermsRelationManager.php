<?php

namespace Modules\Leadership\Filament\Resources\Positions\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Modules\Leadership\Models\LeadershipTerm;
use Modules\Leadership\Models\Position;
use Modules\Leadership\Support\Appointments;
use Platform\Assignments\Assignment;
use Platform\Filament\Components\PersonPicker;
use Platform\Filament\Components\TranslatableTabs;

/**
 * Who holds the position (FR-LEAD-01/05/07): person from any directory or a manual person,
 * term dates, public, message and biography per language. Appointing someone to a
 * single-holder position offers to end the current holder's term (AC-LEAD-01).
 */
class TermsRelationManager extends RelationManager
{
    protected static string $relationship = 'terms';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('leadership::leadership.terms');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->columnSpanFull()->schema([
                PersonPicker::make('person')->label(__('leadership::leadership.fields.person'))->required(),
                Toggle::make('is_public')->label(__('leadership::leadership.fields.is_public'))->default(true)->inline(false),
                DatePicker::make('starts_on')->label(__('leadership::leadership.fields.starts_on'))->default(fn (): string => Carbon::today()->toDateString()),
                DatePicker::make('ends_on')->label(__('leadership::leadership.fields.ends_on'))->afterOrEqual('starts_on')
                    ->helperText(__('leadership::leadership.help.ends_on')),
            ]),
            Toggle::make('end_previous')->label(fn (): string => __('leadership::leadership.end_previous', ['names' => implode(', ', $this->holders())]))
                ->default(true)->visibleOn('create')->visible(fn (): bool => $this->holders() !== [])->columnSpanFull(),
            TranslatableTabs::make()->columnSpanFull()->schema(fn (string $locale): array => [
                RichEditor::make("message.{$locale}")->label(__('leadership::leadership.fields.message'))->fileAttachments(false),
                RichEditor::make("biography.{$locale}")->label(__('leadership::leadership.fields.biography'))
                    ->helperText(__('leadership::leadership.help.biography'))->fileAttachments(false),
            ]),
            Toggle::make('is_active')->label(__('platform::relations.active'))->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('assignment.person')
                ->orderByDesc(Assignment::query()->select('starts_on')->whereColumn('core_assignments.id', 'leadership_terms.assignment_id')))
            ->columns([
                TextColumn::make('person')->label(__('leadership::leadership.fields.person'))
                    ->state(fn (LeadershipTerm $record): string => $record->name()),
                TextColumn::make('tenure')->label(__('leadership::leadership.tenure'))
                    ->state(fn (LeadershipTerm $record): ?string => $record->tenure()),
                TextColumn::make('state')->label('')->badge()
                    ->state(fn (LeadershipTerm $record): string => $record->isFormer() ? __('leadership::leadership.former_one') : __('leadership::leadership.current'))
                    ->color(fn (LeadershipTerm $record): string => $record->isFormer() ? 'gray' : 'success'),
                ToggleColumn::make('is_active')->label(__('platform::relations.active')),
            ])
            ->filters([
                TernaryFilter::make('former')->label(__('leadership::leadership.former_one'))
                    ->queries(true: fn (Builder $query) => $query->former(), false: fn (Builder $query) => $query->current(), blank: fn (Builder $query) => $query),
            ])
            ->headerActions([
                CreateAction::make()->label(__('leadership::leadership.appoint'))->modalWidth('4xl')
                    ->using(fn (array $data): LeadershipTerm => app(Appointments::class)->appoint(
                        $this->position(), PersonPicker::resolve($data['person']), $data, endPrevious: (bool) ($data['end_previous'] ?? false),
                    )),
            ])
            ->recordActions([
                EditAction::make()->modalWidth('4xl')
                    ->mutateRecordDataUsing(fn (array $data, LeadershipTerm $record): array => $data + [
                        'person' => $record->assignment?->person ? PersonPicker::keyOf($record->assignment->person) : null,
                        'starts_on' => $record->assignment?->starts_on?->toDateString(),
                        'ends_on' => $record->assignment?->ends_on?->toDateString(),
                        'is_public' => (bool) $record->assignment?->is_public,
                    ])
                    ->using(fn (LeadershipTerm $record, array $data): LeadershipTerm => app(Appointments::class)->update($record, PersonPicker::resolve($data['person'] ?? null), $data)),
                DeleteAction::make(),
            ]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can('view', $ownerRecord);
    }

    protected function canCreate(): bool
    {
        return (bool) auth()->user()?->can('update', $this->getOwnerRecord());
    }

    protected function canEdit(Model $record): bool
    {
        return $this->canCreate();
    }

    protected function canDelete(Model $record): bool
    {
        return $this->canCreate();
    }

    protected function position(): Position
    {
        /** @var Position */
        return $this->getOwnerRecord();
    }

    /** @return list<string> */
    protected function holders(): array
    {
        return app(Appointments::class)->currentHolders($this->position());
    }
}
