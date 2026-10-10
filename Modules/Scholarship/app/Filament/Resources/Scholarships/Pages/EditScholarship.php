<?php

namespace Modules\Scholarship\Filament\Resources\Scholarships\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Modules\Scholarship\Filament\Resources\Scholarships\ScholarshipResource;
use Modules\Scholarship\Models\Scholarship;

class EditScholarship extends EditRecord
{
    protected static string $resource = ScholarshipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')->label(__('scholarship::scholarship.view_page'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(fn (Scholarship $record): string => url($record->url()))->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
