<?php

namespace Modules\Program\Filament\Resources\Programs\Pages;

use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Modules\Program\Filament\Resources\Programs\ProgramResource;
use Modules\Program\Models\Program;
use Platform\Filament\Hub\MicrositeActions;

/**
 * The Program editor, read only, for who may view but not update the program — e.g.
 * the Admission Office, which edits only the tuition fees (Tuition module) in the Hub. Who
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
            MicrositeActions::group(),   // Menus, Header & Footer, Homepage, Statistics, Pages of this microsite; view it
        ];
    }
}
