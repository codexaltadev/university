<?php

namespace Modules\Administration\Filament\Resources\Members\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Modules\Administration\Filament\Resources\Members\MemberResource;
use Modules\Administration\Models\Member;

class EditMember extends EditRecord
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')->label(__('administration::administration.view_page'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(fn (Member $record): string => url($record->url()))->openUrlInNewTab()
                ->visible(fn (Member $record): bool => $record->isFrontendVisible()),
            DeleteAction::make(),
        ];
    }
}
