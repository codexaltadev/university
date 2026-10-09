<?php

namespace Modules\ActivityLog\Filament\Resources\Activities\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Modules\ActivityLog\Filament\Resources\Activities\ActivityResource;
use Modules\ActivityLog\Services\ActivityExport;
use Platform\Console\Commands\CleanActivityLogCommand;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    public function getSubheading(): string
    {
        return __('activitylog::activity.retention', ['days' => CleanActivityLogCommand::retentionDays()]);
    }

    protected function getHeaderActions(): array
    {
        return [
            // FR-LOG-05: the rows as currently filtered
            Action::make('exportCsv')
                ->label(__('activitylog::activity.export'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn (): StreamedResponse => app(ActivityExport::class)->csv($this->getFilteredSortedTableQuery())),
        ];
    }
}
