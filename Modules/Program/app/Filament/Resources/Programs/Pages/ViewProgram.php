<?php

namespace Modules\Program\Filament\Resources\Programs\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Modules\Program\Filament\Resources\Programs\ProgramResource;
use Modules\Program\Models\Program;

/**
 * The Program editor, read only, for who may view but not update the program — e.g.
 * the Admission Office, which edits only its tuition in the Hub (SRS M-10 v1.1). Who
 * may update gets the editor instead, so the Hub shows one "Profile".
 */
class ViewProgram extends ViewRecord
{
    protected static string $resource = ProgramResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        $record = $parameters['record'] ?? null;

        return parent::canAccess($parameters) && ! ($record instanceof Program && Auth::user()?->can('update', $record));
    }

    public static function getNavigationLabel(): string
    {
        return __('platform::hub.profile');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('program::program.hub.program');
    }

    public function getSubheading(): ?string
    {
        return EditProgram::progressText($this->getRecord());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_site')->label(__('program::program.actions.view_site'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(fn (Program $record): ?string => $record->url())->openUrlInNewTab(),
        ];
    }
}
