<?php

namespace Modules\Slider\Filament\Resources\Slides\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Slider\Filament\Resources\Slides\SlideResource;

class ListSlides extends ListRecords
{
    protected static string $resource = SlideResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
