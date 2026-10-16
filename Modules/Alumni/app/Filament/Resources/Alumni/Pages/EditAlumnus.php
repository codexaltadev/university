<?php

namespace Modules\Alumni\Filament\Resources\Alumni\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Modules\Alumni\Filament\Resources\Alumni\AlumnusResource;
use Modules\Alumni\Models\Alumnus;

class EditAlumnus extends EditRecord
{
    protected static string $resource = AlumnusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')->label(__('alumni::alumni.view_page'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(fn (Alumnus $record): string => url($record->url()))->openUrlInNewTab()
                ->visible(fn (Alumnus $record): bool => $record->isFrontendVisible()),
            DeleteAction::make(),
        ];
    }
}
