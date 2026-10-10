<?php

namespace Modules\Program\Filament\Resources\Programs\Hub;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Modules\Program\Filament\Resources\Programs\ProgramResource;
use Platform\Filament\Hub\HubRelationPage;

/**
 * Sub-items edited in the Program Hub, one part each (BRL-PROG-04, FR-GEN-86, group "Program page"). They have no policy of their
 * own: whoever may update the Program — its scope included — may edit them
 * (AC-PROG-03); whoever may view it sees them.
 */
abstract class ProgramItemsPage extends HubRelationPage
{
    protected static string $resource = ProgramResource::class;

    /** @return list<Column> */
    abstract protected function columns(): array;

    /** Fixed values for new rows, e.g. the list type. */
    protected function defaults(): array
    {
        return [];
    }

    /** Narrow the relationship, e.g. to one list type. */
    protected function scopeQuery(Builder $query): Builder
    {
        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $this->scopeQuery($query->withoutGlobalScopes([SoftDeletingScope::class])))
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ...$this->columns(),
                ToggleColumn::make('is_active')->label(__('platform::relations.active'))
                    ->disabled(fn (): bool => ! $this->ownerAllows('update')),
            ])
            ->filters([TrashedFilter::make()])
            ->headerActions([
                CreateAction::make()->mutateDataUsing(fn (array $data): array => [...$data, ...$this->defaults()]),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }

    public static function canAccess(array $parameters = []): bool
    {
        $program = $parameters['record'] ?? null;

        return $program instanceof Model && (bool) Auth::user()?->can('view', $program);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('program::program.hub.page');
    }

    protected function ownerAllows(string $ability): bool
    {
        return (bool) Auth::user()?->can($ability, $this->getOwnerRecord());
    }

    protected function ownerResponse(string $ability): Response
    {
        return $this->ownerAllows($ability) ? Response::allow() : Response::deny();
    }

    protected function getViewAnyAuthorizationResponse(): Response
    {
        return $this->ownerResponse('view');
    }

    protected function getViewAuthorizationResponse(Model $record): Response
    {
        return $this->ownerResponse('view');
    }

    protected function getCreateAuthorizationResponse(): Response
    {
        return $this->ownerResponse('update');
    }

    protected function getEditAuthorizationResponse(Model $record): Response
    {
        return $this->ownerResponse('update');
    }

    protected function getDeleteAuthorizationResponse(Model $record): Response
    {
        return $this->ownerResponse('update');
    }

    protected function getDeleteAnyAuthorizationResponse(): Response
    {
        return $this->ownerResponse('update');
    }

    protected function getForceDeleteAuthorizationResponse(Model $record): Response
    {
        return $this->ownerResponse('update');
    }

    protected function getForceDeleteAnyAuthorizationResponse(): Response
    {
        return $this->ownerResponse('update');
    }

    protected function getRestoreAuthorizationResponse(Model $record): Response
    {
        return $this->ownerResponse('update');
    }

    protected function getRestoreAnyAuthorizationResponse(): Response
    {
        return $this->ownerResponse('update');
    }

    protected function getReorderAuthorizationResponse(): Response
    {
        return $this->ownerResponse('update');
    }

    protected function getReplicateAuthorizationResponse(Model $record): Response
    {
        return $this->ownerResponse('update');
    }
}
