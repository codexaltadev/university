<?php

namespace Platform\Filament\Tables;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Platform\Hierarchy\EntityHierarchy;
use Platform\Relations\RelationEngine;
use Platform\Relations\Targets;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * Standard filters of content lists (FR-GEN-21, FR-REL-04, FR-CNT-C05): where it is
 * shown, main site, category. Same look in every content module:
 *
 *     ->filters([ContentFilters::shownIn(), ContentFilters::mainSite(), ContentFilters::term('category', 'faq.category')])
 */
class ContentFilters
{
    /** Shown in these faculties / departments / offices …, optionally with everything below them. */
    public static function shownIn(): Filter
    {
        return Filter::make('shown_in')
            ->schema([
                Select::make('places')->label(__('platform::relations.show_in'))
                    ->multiple()->searchable()
                    ->options(fn (): array => app(Targets::class)->options(Auth::user())),
                Toggle::make('with_children')->label(__('platform::relations.with_children'))->default(true),
            ])
            ->query(function (Builder $query, array $data): Builder {
                $places = array_values((array) ($data['places'] ?? []));

                if ($places === []) {
                    return $query;
                }

                if ($data['with_children'] ?? true) {
                    $places = app(EntityHierarchy::class)->withDescendants($places);
                }

                return $query->shownIn($places);
            })
            ->indicateUsing(function (array $data): array {
                return collect((array) ($data['places'] ?? []))
                    ->map(fn (string $key): Indicator => Indicator::make(app(Targets::class)->label($key))->removeField('places'))
                    ->all();
            });
    }

    /** HasOwner records (pages, menus …): the main site's or an entity's. */
    public static function owner(): SelectFilter
    {
        return SelectFilter::make('owner')
            ->label(__('platform::relations.owner'))
            ->searchable()
            ->options(fn (): array => static::ownerOptions())
            ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null) ? $query->ownedBy($data['value']) : $query);
    }

    /**
     * Owners this user may choose: the main site for unscoped users, then the
     * entities of their scope, grouped by kind.
     *
     * @return array<string, string|array<string, string>>
     */
    public static function ownerOptions(): array
    {
        $user = Auth::user();
        $unscoped = $user === null || $user->isSuperAdmin() || app(Targets::class)->highlightableFor($user) === null;

        return [
            ...($unscoped ? [RelationEngine::SITE_KEY => '🌐 '.__('platform::relations.main_site')] : []),
            ...app(Targets::class)->options($user),
        ];
    }

    /** @return list<string> every owner key of ownerOptions() */
    public static function ownerKeys(): array
    {
        return collect(static::ownerOptions())
            ->flatMap(fn ($value, $key): array => is_array($value) ? array_keys($value) : [$key])
            ->values()->all();
    }

    /** On the main site, or only in entities (FR-CNT-H07). */
    public static function mainSite(): TernaryFilter
    {
        $site = fn (Builder $query, bool $exists): Builder => $query->{$exists ? 'whereExists' : 'whereNotExists'}(
            fn (QueryBuilder $sub) => $sub->selectRaw('1')->from('core_relations as ms')
                ->whereColumn('ms.content_id', $query->getModel()->getQualifiedKeyName())
                ->where('ms.content_type', $query->getModel()->getMorphClass())
                ->where('ms.target_type', RelationEngine::SITE_TYPE),
        );

        return TernaryFilter::make('main_site')->label(__('platform::relations.main_site'))
            ->trueLabel(__('platform::relations.on_main_site_yes'))
            ->falseLabel(__('platform::relations.on_main_site_no'))
            ->queries(
                true: fn (Builder $query) => $site($query, true),
                false: fn (Builder $query) => $site($query, false),
                blank: fn (Builder $query) => $query,
            );
    }

    /** Terms of a vocabulary; a parent category includes its sub-categories (HasTaxonomy). */
    public static function term(string $name, string $vocabulary): SelectFilter
    {
        return SelectFilter::make($name)
            ->label(fn (): string => app(Vocabularies::class)->find($vocabulary)->name)
            ->multiple()
            ->searchable()
            ->options(fn (): array => app(Vocabularies::class)->options($vocabulary, activeOnly: false))
            ->query(function (Builder $query, array $data): Builder {
                $ids = array_map('intval', (array) ($data['values'] ?? []));

                if ($ids === []) {
                    return $query;
                }

                $all = Term::query()->whereKey($ids)->get()->flatMap(fn (Term $term): array => $term->descendantIds())->unique()->all();

                return $query->whereHas('terms', fn (Builder $q) => $q->whereKey($all));
            });
    }

    /** Short labels of the places a record is shown in ("CSE, EEE"), main site first. */
    public static function placesOf($record): array
    {
        $targets = app(Targets::class);

        return collect($record->contentRelations ?? [])
            ->map(fn ($relation): string => $relation->targetKey())
            ->sortBy(fn (string $key): string => $key === RelationEngine::SITE_KEY ? '0' : '1'.$key)
            ->map(fn (string $key): string => $key === RelationEngine::SITE_KEY ? '🌐 '.__('platform::relations.main_site') : Str::after($targets->label($key), ': '))
            ->values()->all();
    }
}
