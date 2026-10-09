<?php

namespace Platform\Filament\Components;

use App\Models\User;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Platform\Relations\RelationEngine;
use Platform\Relations\Targets;

/**
 * Content form sidebar, "Show in" (FR-GEN-50/51, FR-CNT-C01/H07/H09, UI/UX Part A §6):
 * entities the content appears in, the main-site switch (PublishMain only; on by
 * default for those users) and, for everyone else, "ask the Admin". Saved through the
 * Relation Engine together with HighlightPanel's choices.
 *
 *     RelatedToField::make()
 */
class RelatedToField
{
    public static function make(): Section
    {
        $user = fn (): ?User => Auth::user();
        $mayPublish = fn (Component $component): bool => app(RelationEngine::class)->mayPublishMain(Auth::user(), static::modelClass($component));

        return Section::make(__('platform::relations.show_in'))
            ->compact()
            ->schema([
                Select::make('show_in')->hiddenLabel()
                    ->multiple()
                    ->searchable()
                    ->options(fn (): array => app(Targets::class)->options($user()))
                    ->placeholder(__('platform::relations.show_in_placeholder'))
                    ->helperText(__('platform::relations.show_in_help'))
                    ->default(fn (): array => app(Targets::class)->defaultFor($user()))
                    // FR-GEN-51: a scoped user needs one of their entities; the engine's own rules, before saving
                    ->rules([fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        foreach (app(RelationEngine::class)->targetErrors((array) $value, Auth::user()) as $error) {
                            $fail($error);
                        }
                    }])
                    ->live()
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Select $component, ?Model $record): void {
                        if ($record?->exists) {
                            $component->state($record->targetKeys());
                        }
                    })
                    ->saveRelationshipsUsing(fn (Model $record, Get $get) => static::save($record, $get)),
                Toggle::make('main_site')->label(__('platform::relations.on_main_site'))
                    ->helperText(__('platform::relations.on_main_site_help'))
                    ->visible($mayPublish)
                    ->default(true)   // FR-CNT-H07: on for content made by those who decide it
                    ->live()
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Toggle $component, ?Model $record): void {
                        if ($record?->exists) {
                            $component->state($record->isOnMainSite());
                        }
                    }),
                Toggle::make('request_main_site')->label(__('platform::relations.request_main_site'))
                    ->helperText(__('platform::relations.request_main_site_help'))
                    ->visible(fn (Component $component, ?Model $record): bool => ! $mayPublish($component) && ! $record?->isOnMainSite())
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Toggle $component, ?Model $record): void {
                        if ($record?->exists) {
                            $component->state(app(RelationEngine::class)->hasPendingRequest($record));
                        }
                    }),
            ]);
    }

    /** Targets, main site and highlights in one Relation Engine call (SAD Part B §3.2). */
    public static function save(Model $record, Get $get): void
    {
        $engine = app(RelationEngine::class);
        $user = Auth::user();
        $targets = array_values((array) $get('show_in'));
        $mayPublish = $engine->mayPublishMain($user, $record);

        $engine->sync(
            $record,
            $targets,
            mainSite: $mayPublish ? (bool) $get('main_site') : null,
            highlights: HighlightPanel::collect($get, [...$targets, ...($mayPublish ? ($get('main_site') ? [RelationEngine::SITE_KEY] : []) : ($record->isOnMainSite() ? [RelationEngine::SITE_KEY] : []))]),
            user: $user,
        );

        if (! $mayPublish && ! $record->isOnMainSite()) {
            $get('request_main_site') ? $engine->requestMainSite($record, $user) : $engine->withdrawRequest($record);
        }
    }

    /** @return class-string<Model> */
    public static function modelClass(Component $component): string
    {
        $model = $component->getModel();

        return is_object($model) ? $model::class : (string) $model;
    }
}
