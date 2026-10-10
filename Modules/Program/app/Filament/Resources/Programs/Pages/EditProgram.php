<?php

namespace Modules\Program\Filament\Resources\Programs\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Modules\Program\Filament\Resources\Programs\ProgramResource;
use Modules\Program\Filament\Resources\Programs\Schemas\ProgramForm;
use Modules\Program\Models\Program;
use Modules\Program\Support\ProgramChecklist;

/** The Program editor (UI/UX Part A §5.2 v1.2): every part as a tab on the left, with what is filled in. */
class EditProgram extends EditRecord
{
    protected static string $resource = ProgramResource::class;

    /** "Profile" in the Program Hub, group "Program". */
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
        return static::progressText($this->getRecord());
    }

    public static function progressText(Program $program): string
    {
        return __('program::program.progress', ProgramChecklist::progress($program));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')->label(__('program::program.actions.view_site'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(fn (Program $record): ?string => $record->url())->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }

    /** A scoped user cannot move the Program (BRL-PROG-02) or change its name, short name or activity, whatever the request says. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (ProgramForm::profileOnly()) {
            unset($data['department_id'], $data['name'], $data['short_name'], $data['is_active']);
        }

        return $data;
    }
}
