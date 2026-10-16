<?php

namespace Modules\Achievements\Filament\Resources\Achievements\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Modules\Achievements\Filament\Resources\Achievements\AchievementResource;
use Modules\Achievements\Models\Achievement;

class ListAchievements extends ListRecords
{
    protected static string $resource = AchievementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('website')->label(__('achievements::achievements.view_site'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(url('/'.Achievement::PATH))->openUrlInNewTab(),
        ];
    }
}
