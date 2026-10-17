<?php

namespace Modules\FacultyMember\Filament\Resources\FacultyMembers\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Modules\FacultyMember\Filament\Resources\FacultyMembers\FacultyMemberResource;
use Modules\FacultyMember\Models\FacultyMember;

/** A teacher: the faculty site's data read only; only the contact switches are saved (BRL-FM-03). */
class EditFacultyMember extends EditRecord
{
    protected static string $resource = FacultyMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')->label(__('facultymember::facultymember.view_page'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(fn (FacultyMember $record): string => url($record->url()))->openUrlInNewTab()
                ->visible(fn (FacultyMember $record): bool => $record->isFrontendVisible()),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return Arr::only($data, ['show_email', 'show_phone', 'show_extension']);
    }
}
