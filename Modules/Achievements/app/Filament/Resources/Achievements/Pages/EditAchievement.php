<?php

namespace Modules\Achievements\Filament\Resources\Achievements\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Modules\Achievements\Filament\Resources\Achievements\AchievementResource;
use Modules\Achievements\Models\Achievement;

class EditAchievement extends EditRecord
{
    protected static string $resource = AchievementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')->label(__('achievements::achievements.view_page'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(fn (Achievement $record): string => url($record->url()))->openUrlInNewTab()
                ->visible(fn (Achievement $record): bool => $record->isFrontendVisible()),
            DeleteAction::make(),
        ];
    }
}
