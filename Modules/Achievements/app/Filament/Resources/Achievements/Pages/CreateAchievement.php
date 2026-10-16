<?php

namespace Modules\Achievements\Filament\Resources\Achievements\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Achievements\Filament\Resources\Achievements\AchievementResource;

class CreateAchievement extends CreateRecord
{
    protected static string $resource = AchievementResource::class;
}
