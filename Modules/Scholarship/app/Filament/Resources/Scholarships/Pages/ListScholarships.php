<?php

namespace Modules\Scholarship\Filament\Resources\Scholarships\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Modules\Scholarship\Filament\Resources\Scholarships\ScholarshipResource;
use Modules\Scholarship\Models\Scholarship;

class ListScholarships extends ListRecords
{
    protected static string $resource = ScholarshipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('website')->label(__('scholarship::scholarship.view_site'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(url('/'.Scholarship::PATH))->openUrlInNewTab(),
        ];
    }
}
