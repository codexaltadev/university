<?php

namespace Platform\Filament\Hub;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Platform\Context\WorkingIn;

/**
 * "Microsite ▾" on a microsite entity's editor (Faculty, Department, Program …, UI/UX Part A
 * §4.1): the microsite screens the user may use for this entity — Menus, Header & Footer,
 * Homepage, Statistics, Pages — opened on it, each marked ✓ set up / ! not yet (the
 * microsite overview, §4.2), and the microsite itself.
 */
class MicrositeActions
{
    public static function group(): ActionGroup
    {
        $tools = collect(WorkingIn::tools())->map(fn (string $tool): Action => Action::make('microsite_'.class_basename($tool))
            ->label(fn (): string => $tool::getNavigationLabel())
            ->icon(fn () => $tool::getNavigationIcon())
            ->url(fn (Model $record): string => $tool::urlFor(WorkingIn::keyOf($record)))
            ->badge(fn (Model $record): string => $tool::isSetUpFor(WorkingIn::keyOf($record)) ? '✓' : '!')
            ->badgeColor(fn (Model $record): string => $tool::isSetUpFor(WorkingIn::keyOf($record)) ? 'success' : 'warning')
            ->visible(fn (Model $record): bool => $record->exists && $tool::canUseFor(WorkingIn::keyOf($record))))
            ->all();

        return ActionGroup::make([
            ...$tools,
            Action::make('microsite_view')->label(__('platform::hub.view_microsite'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->url(fn (Model $record): ?string => method_exists($record, 'url') ? $record->url() : null)->openUrlInNewTab()
                ->visible(fn (Model $record): bool => method_exists($record, 'url') && filled($record->url())),
        ])
            ->label(__('platform::hub.microsite'))
            ->icon(Heroicon::OutlinedGlobeAlt)
            ->color('gray')
            ->button();
    }
}
