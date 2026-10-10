<?php

namespace Modules\Scholarship\Filament\Resources\Scholarships\Tables;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Modules\Scholarship\Filament\Resources\Scholarships\ScholarshipResource;
use Modules\Scholarship\Models\Scholarship;
use Platform\Taxonomy\Vocabularies;

/** Scholarships and their parts; "Under" filters one scholarship's parts (Erasmus → its calls). */
class ScholarshipsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['parent.terms', 'terms'])->withCount('parts'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('title')->label(__('scholarship::scholarship.fields.title'))->searchable()->wrap()
                    ->description(fn (Scholarship $record): ?string => $record->parent
                        ? __('scholarship::scholarship.under', ['title' => $record->parent->title])
                        : ($record->parts_count ? trans_choice('scholarship::scholarship.parts_count', $record->parts_count, ['count' => $record->parts_count]) : null)),
                TextColumn::make('category')->label(__('scholarship::scholarship.fields.category'))->badge()
                    ->state(fn (Scholarship $record): ?string => ($record->parent ?? $record)->termsIn(Scholarship::CATEGORY)->first()?->name),
                TextColumn::make('deadline_at')->label(__('scholarship::scholarship.fields.deadline_at'))->dateTime('d M Y')->sortable()
                    ->badge()->color(fn (Scholarship $record): string => $record->isClosed() ? 'gray' : 'success')
                    ->description(fn (Scholarship $record): ?string => $record->deadline_at ? ($record->isClosed() ? __('scholarship::scholarship.closed') : __('scholarship::scholarship.open')) : null),
                ToggleColumn::make('is_active')->label(__('platform::relations.active'))
                    ->disabled(fn ($record): bool => ! auth()->user()?->can('update', $record)),
            ])
            ->filters([
                SelectFilter::make('parent_id')->label(__('scholarship::scholarship.fields.parent'))
                    ->options(fn (): array => ['top' => __('scholarship::scholarship.top_level')]
                        + Scholarship::query()->topLevel()->has('parts')->ordered()->get()->mapWithKeys(fn (Scholarship $s): array => [$s->id => $s->title])->all())
                    ->query(fn ($query, array $data) => match ($data['value'] ?? null) {
                        null, '' => $query,
                        'top' => $query->whereNull('parent_id'),
                        default => $query->where('parent_id', $data['value']),
                    }),
                SelectFilter::make('category')->label(__('scholarship::scholarship.fields.category'))
                    ->options(fn (): array => app(Vocabularies::class)->options(Scholarship::CATEGORY))
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->where(fn ($q) => $q->withTerm((int) $data['value'])->orWhereHas('parent', fn ($p) => $p->withTerm((int) $data['value'])))
                        : $query),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('addPart')->label(__('scholarship::scholarship.add_part'))->icon(Heroicon::OutlinedPlus)->color('gray')
                    ->url(fn (Scholarship $record): string => ScholarshipResource::getUrl('create', ['parent' => $record->id]))
                    ->visible(fn (Scholarship $record): bool => $record->parent_id === null && (bool) auth()->user()?->can('create', Scholarship::class)),
                EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make(),
            ]);
    }
}
