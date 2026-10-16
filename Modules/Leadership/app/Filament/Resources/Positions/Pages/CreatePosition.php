<?php

namespace Modules\Leadership\Filament\Resources\Positions\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Leadership\Filament\Resources\Positions\PositionResource;
use Modules\Leadership\Models\Position;

/** After creating, the edit page opens so someone can be appointed. */
class CreatePosition extends CreateRecord
{
    protected static string $resource = PositionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data + ['sort_order' => (int) Position::query()->max('sort_order') + 1];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
