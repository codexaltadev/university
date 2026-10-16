<?php

namespace Modules\Committee\Filament\Resources\Committees\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Modules\Committee\Filament\Resources\Committees\CommitteeResource;
use Modules\Committee\Models\Committee;

class EditCommittee extends EditRecord
{
    protected static string $resource = CommitteeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')->label(__('committee::committee.view_page'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(fn (Committee $record): string => url($record->url()))->openUrlInNewTab()
                ->visible(fn (Committee $record): bool => $record->isFrontendVisible()),
            CommitteeResource::copyAction(),
            DeleteAction::make(),
        ];
    }
}
