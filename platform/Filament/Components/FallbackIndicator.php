<?php

namespace Platform\Filament\Components;

use Closure;
use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Platform\Context\MicrositeOwners;

/**
 * "This place uses Engineering Faculty's …" with "Create own" / "Copy … and edit"
 * (UI/UX Part A §6, U-5, FR-MENU-08/09): one look for menus, header & footer,
 * homepage, statistics and templates.
 *
 *     FallbackIndicator::make(
 *         from: fn (): ?string => $this->current()['from'],          // owner key, null = theme default
 *         createOwn: fn () => $this->createOwn(false),
 *         copy: fn () => $this->createOwn(true),                        // optional
 *     )->visible(fn (): bool => $this->own() === null)
 */
class FallbackIndicator
{
    /**
     * @param  Closure(): ?string  $from  where it comes from now: owner key, or null (theme default)
     * @param  Closure(): mixed  $createOwn
     * @param  Closure(): mixed|null  $copy
     * @param  string|null  $noneText  shown when nothing applies (default: the theme's default)
     */
    public static function make(Closure $from, Closure $createOwn, ?Closure $copy = null, ?string $noneText = null, ?string $createLabel = null): Section
    {
        return Section::make()
            ->extraAttributes(['class' => 'uwms-fallback-indicator'])
            ->schema([
                Text::make(function () use ($from, $noneText): string {
                    $owner = $from();

                    return $owner === null
                        ? ($noneText ?? __('platform::relations.fallback.none'))
                        : __('platform::relations.fallback.uses', ['owner' => MicrositeOwners::label($owner)]);
                }),
                Actions::make([
                    Action::make('createOwn')->label($createLabel ?? __('platform::relations.fallback.create_own'))
                        ->icon('heroicon-o-plus')
                        ->action($createOwn),
                    ...($copy ? [
                        Action::make('copyCurrent')
                            ->label(fn (): string => __('platform::relations.fallback.copy', ['owner' => MicrositeOwners::label((string) $from())]))
                            ->icon('heroicon-o-document-duplicate')->color('gray')
                            ->visible(fn (): bool => $from() !== null)
                            ->action($copy),
                    ] : []),
                ]),
            ]);
    }
}
